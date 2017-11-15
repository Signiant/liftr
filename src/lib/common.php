<?php

function dump_var($var)
{
	print "<pre>";print_r($var);print "</pre>";
}

function doAlert($type,$dismissable,$msg)
{
    $type = strtolower($type);

    switch ($type)
    {
        case "success":
            $class = "alert-success";
            $glyph = "glyphicon-ok-sign";
            break;
        case "info":
            $class = "alert-info";
            $glyph = "glyphicon-info-sign";
            break;
        case "warning":
            $class = "alert-warning";
            $glyph = "glyphicon-warning-sign";
            break;
        case "danger":
            $class = "alert-danger";
            $glyph = "glyphicon-exclamation-sign";
            break;
        default:
            $class = "alert-info";
            $glyph = "glyphicon-info-sign";
            break;
    }
    if ($dismissable)
    {
        print "<div class='alert " . $class . " alert-dismissable'>\n";
        print "<button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>\n";
        print "<span class='glyphicon " . $glyph . "'></span>\n";
        print "<strong>" . $msg . "</strong>\n";
        print "</div>\n";
    } else
    {
        print "<div class='alert " . $class . "'>\n";
        print "<span class='glyphicon " . $glyph . "'></span>\n";
        print "<strong>" . $msg . "</strong>\n";
        print "</div>\n";
    }
}

# loop over config and get the info for each record
function renderRecords($r53Client,$config)
{
	error_log("renderRecords");

	$i = 1;

	foreach ($config['weighted_dns'] as $dnsRecord)
	{
		# For the current fqdn, get the weighted records associated with them.
		# Our tool only handles 2 weighted records per fqdn...
		$records = getWeightedRecords($r53Client, $dnsRecord['name'] ,$dnsRecord['zoneid']);

		if ($records)
		{
			if (count($records) != 2)
			{
				doAlert('alert-danger',"This tool only handles records with 2 sets. " . $dnsRecord['name'] . " has " . count($records));
			} else {
				$fqdn = $records[0]['Name'];
				$sliderId = 'slider' . $i;

				$leftTextboxId = 'slider' . $i . "-left";
				$leftSetId = $records[0]['SetIdentifier'];
				$leftSetWeight = $records[0]['Weight'];

				$rightTextboxId = 'slider' . $i . "-right";
				$rightSetId = $records[1]['SetIdentifier'];
				$rightSetWeight = $records[1]['Weight'];
				?>

				<div class="panel panel-default">
			    <div class="panel-heading">
			      <h3 class="panel-title"><?= $fqdn ?></h3>
			    </div>
			    <div class='panel-body'>
						<div class="row">
							<form method='post' action=''>
								<div class="col-sm-3">
									<strong><?= $leftSetId ?></strong>
								</div>
								<div class="col-sm-1">
									<input type="hidden" name="left-set-name" value="<?= $leftSetId ?>">
									<input name="left-set-value" id="<?= $leftTextboxId ?>" class="form-control slider-txt" type="text" pattern="\d*" data-inputmask="" value=""/>
								</div>
								<div class="col-sm-3">
									<input id="<?= $sliderId ?>"  type="text" data-slider-min="0" data-slider-max="100" data-slider-step="1"/>
								</div>
								<div class="col-sm-1">
									<input type="hidden" name="right-set-name" value="<?= $rightSetId ?>">
									<input name="right-set-value" id="<?= $rightTextboxId ?>" class="form-control slider-txt" type="text" pattern="\d*" data-inputmask="" value=""/>
								</div>
								<div class="col-sm-3">
									<strong><?= $rightSetId ?></strong>
								</div>
								<div class="col-sm-1">
									<input type="hidden" id="fqdn" name="fqdn" value="<?= $fqdn ?>">
									<input type="hidden" id="zoneid" name="zoneid" value="<?= $dnsRecord['zoneid'] ?>">
									<button type="submit" class="btn btn-primary">Adjust</button>
								</div>
							</form>
						</div>

						<script>
							var minSliderValue = $("#<?= $sliderId ?>").data("slider-min");
							var maxSliderValue = $("#<?= $sliderId ?>").data("slider-max");

							var <?= $sliderId ?> = $('#<?= $sliderId ?>').slider({
							  value: <?= $leftSetWeight ?>,
								formatter: function(value) {
									return 'Weight: ' + value;
								}
							});

							$("#<?= $leftTextboxId ?>").val(<?= $sliderId ?>.slider('getValue'));
							$("#<?= $rightTextboxId ?>").val(maxSliderValue-(<?= $sliderId ?>.slider('getValue')));

							// If You want to change input text using slider handler
							$('#<?= $sliderId ?>').on('slide', function(slider){
								$("#<?= $leftTextboxId ?>").val(slider.value);
							  $("#<?= $rightTextboxId ?>").val(maxSliderValue-(slider.value));
							});

							// If you want to change slider using input text (left box)
							$("#<?= $leftTextboxId ?>").on("keyup", function() {
							    var val = Math.abs(parseInt(this.value, 10) || minSliderValue);
							    this.value = val > maxSliderValue ? maxSliderValue : val;

							    $('#<?= $sliderId ?>').slider('setValue', val);
									$("#<?= $rightTextboxId ?>").val(maxSliderValue-(<?= $sliderId ?>.slider('getValue')));
							});

							// If you want to change slider using input text (right box)
							$("#<?= $rightTextboxId ?>").on("keyup", function() {
									var val = Math.abs(parseInt(this.value, 10) || minSliderValue);
									this.value = val > maxSliderValue ? maxSliderValue : val;

									$('#<?= $sliderId ?>').slider('setValue', val);
									$("#<?= $$leftTextboxId ?>").val(maxSliderValue-(<?= $sliderId ?>.slider('getValue')));
							});
						</script>
					</div> <!-- panel body -->
				</div> <!-- panel -->
				<?php
			}
		} else {
			doAlert('alert-danger',"No Weighted records found for " . $dnsRecord['name']);
		}
		$i++;
	}
}
?>
