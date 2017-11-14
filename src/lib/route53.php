<?php

# Create a change set to set the new values in the existing records
function updateRoute53WeightedRecord($r53Client, $existingRecords, $POST)
{
  $status = 0;

  # dump_var($POST);

  # Where are the sets in the records?
  if ($POST['left-set-name'] == $existingRecords[0]['SetIdentifier'])
  {
    $leftSetIndex = 0;
    error_log("Found the left set index is 0");
  } elseif ($POST['left-set-name'] == $existingRecords[1]['SetIdentifier'])
  {
    $leftSetIndex = 1;
    error_log("Found the left set index is 1");
  } else {
    error_log("FATAL: unable to find " . $POST['left-set-name'] . " in the existing R53 records");
    $status = 1;
  }

  if ($POST['right-set-name'] == $existingRecords[0]['SetIdentifier'])
  {
    $rightSetIndex = 0;
    error_log("Found the right set index is 0");
  } elseif ($POST['right-set-name'] == $existingRecords[1]['SetIdentifier'])
  {
    $rightSetIndex = 1;
    error_log("Found the right set index is 1");
  } else {
    error_log("FATAL: unable to find " . $POST['right-set-name'] . " in the existing R53 records");
    $status = 1;
  }

  if ($status != 1)
  {
    # We know where the sets are, now update the weights
    $existingRecords[$leftSetIndex]['Weight'] = $POST['left-set-value'];
    $existingRecords[$rightSetIndex]['Weight'] = $POST['right-set-value'];
    # dump_var($existingRecords);

    # Now we can start to build the change set...
    try {
      $result = $r53Client->changeResourceRecordSets([
        'ChangeBatch' => [
          'Changes' => [
            [
              'Action' => 'UPSERT',
              'ResourceRecordSet' => $existingRecords[$leftSetIndex],
            ],
            [
              'Action' => 'UPSERT',
              'ResourceRecordSet' => $existingRecords[$rightSetIndex],
            ],
          ],
          'Comment' => 'Updated by Liftr'
        ],
        'HostedZoneId' => $POST['zoneid']
      ]);
    } catch (Exception $e) {
        error_log("updateRoute53WeightedRecord EXCEPTION: " . $e->getMessage());
    }

    if ($result)
    {
      if ($result['ChangeInfo']['Status'] == "PENDING")
      {
        error_log("Request submitted to R53 and status is PENDING");
        $status = 0;
      } else {
        error_log("Request submitted to R53 and status is " . $result['ChangeInfo']['Status']);
        $status = 2;
      }
    }
    # dump_var($result);
  }

  return $status;
}

function getWeightedRecords($r53Client, $fqdn, $zoneid)
{
  $records = array();
  error_log("getweightedrecords: " . $zoneid . " fqdn " . $fqdn);

  # make sure we always have a trailing dot
  $fqdn = rtrim($fqdn, '.') . '.';

  try {
    $result = $r53Client->listResourceRecordSets([
      'HostedZoneId' => $zoneid,
      'StartRecordName' => $fqdn
    ]);
  } catch (Exception $e) {
      error_log("getWeightedRecords EXCEPTION: " . $e->getMessage());
  }

  if ($result)
  {
    error_log("Results returned from Route53 for " . $fqdn . " - checking for weighted matches");
    # grab records for our fqdn from the return set since everything is returned
    # starting at StartRecordName
    foreach ($result['ResourceRecordSets'] as $record)
    {
      # is this our record and is it weighted?
      if ( $record['Name'] == $fqdn && isWeighted($record) )
      {
        error_log("Weighted record found for " . $fqdn . " with set ID " . $record['SetIdentifier']);
        array_push($records,$record);
      }
    }
  }
  return $records;
}

# Tests if a route53 record is weighted by checking for the 2 weight attributes
function isWeighted($record)
{
  return array_key_exists("Weight",$record) && array_key_exists("SetIdentifier",$record);
}

?>
