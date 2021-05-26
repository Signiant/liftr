<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link rel="shortcut icon" href="images/liftr.png">

    <title>Liftr</title>

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.9.0/bootstrap-slider.min.js"></script>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/9.9.0/css/bootstrap-slider.min.css">
    <!-- Custom styles for this template -->
    <link href="navbar.css" rel="stylesheet">

  </head>
  <body>
    <div class="container">
      <?php
			 	if (file_exists("menu.php")) { include 'menu.php'; }
			 ?>

			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">Route53 Weighted Record Updates</h3>
				</div>
				<div class='panel-body'>
					<div class="jumbotron">
						<h1><img src="images/liftr.png" width="15%" height="15%"> &nbsp;Liftr</h1>
						<p>Updates to Route53 weighted records for the masses</p>
					</div>
				</div>
			</div>

			<?php
                require 'vendor/autoload.php';
                require "lib/common.php";
                require "lib/config.php";
                require "lib/route53.php";
                ob_implicit_flush(TRUE);
                date_default_timezone_set(@date_default_timezone_get());

                # needed so that the aws sdk can find the credentials if bind mounting
                # Seems the AWS PHP SDK does not read the AWS_SHARED_CREDENTIALS_FILE as boto does
                putenv("HOME=/var/www");

                // Read the config File
                $configFile = $_GET['config'];
                if (empty($configFile)) { $configFile = 'config.yaml'; }

                $appConfig = readConfig($configFile);

                // Setup the AWS Sdk
                // Route53 must use the us-east endpoint
                $sharedConfig = [
                    'region' => 'us-east-1',
                    'version' => 'latest'
                ];

                $sdk = new Aws\Sdk($sharedConfig);
                $r53Client = $sdk->createRoute53();

				if($_SERVER['REQUEST_METHOD'] != 'POST')
				{
					renderRecords($r53Client, $appConfig);
				}
			 ?>

<?php
if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	# get the current values from r53
	$records = getWeightedRecords($r53Client, $_POST['fqdn'] ,$_POST['zoneid']);

	if (count($records) != 2)
	{
		# This should really never happen as we've already displayed the values....
		doAlert("danger", "0", "Unexpected number of records returned. Expected 2, received " . count($records));
	} else {
		# Update the record in Route53
		$updateResult = updateRoute53WeightedRecord($r53Client, $records, $_POST);

		if ($updateResult != 0)
		{
			$alertType = "danger";
			$alertText = "Error updating record " . $_POST['fqdn'] . "(" . $updateResult . ")";
		} else {
			$alertType = "success";
			$alertText = "Successfully submitted request to Route53 to update " . $_POST['fqdn'];
		}
		doAlert($alertType, 0, $alertText);
		print "<a href='/'><button type='button' class='btn btn-primary'>Return to List</button></a>\n";
	}
}
?>


</div> <!-- /container -->

</body>
</html>
