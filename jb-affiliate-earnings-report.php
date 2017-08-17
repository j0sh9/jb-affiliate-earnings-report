<?php
/*
Plugin Name: _Affiliate Earnings Report
Description: Admin Page for affilaite reports and overrides
Version: 1.0
*/

add_action( 'admin_menu', 'jb_affiliate_earnings_report' );
function jb_affiliate_earnings_report() {
	$jb_page_title = 'Affiliate Earnings Report';
	$jb_menu_title = 'Affiliate Earnings';
	$jb_capability = 'manage_affiliates';
	$jb_menu_slug = 'jb-affiliate-earnings';
	$jb_callback = 'jb_affilaite_earnings_report_html';
	$jb_icon_url = 'dashicons-chart-pie';
	$jb_menu_position = 120;
	add_menu_page(  $jb_page_title,  $jb_menu_title,  $jb_capability,  $jb_menu_slug,  $jb_callback,  $jb_icon_url,  $jb_menu_position );
}

function jb_affilaite_earnings_report_html() {
?>
	<style>
		.jb-affiliate-report, .jb-affiliate-report th, .jb-affiliate-report td {
			border: 1px solid #cdcdcd;
			border-collapse: collapse;
		}
		.jb-affiliate-report {
			width: 97%;
			margin: 3vw 1vw;
		}
		.jb-affiliate-report td {
			padding: 4px 8px;
		}
	</style>
	<h2>Affiliate Earnings Report</h2>
	<div>
	<?php
/** Date Range **/
	if ( isset( $_POST['start_date'] ) && isset( $_POST['end_date'] ) ) {
		$start_date = $_POST['start_date'].' 00:00:00';
		$end_date = $_POST['end_date'].' 23:59:59';
		$referral_status = $_POST['referral_status'];
	} else {
		$start_date = date('Y-m-d 00:00:00');
		$end_date = date('Y-m-d 23:59:59');
		$referral_status = '';
	}
	$start_url = str_replace('-','%2f',date('m-d-Y',strtotime($start_date)));
	$end_url = str_replace('-','%2f',date('m-d-Y',strtotime($end_date)));
	$start_name = date('Ymd',strtotime($start_date));
	$end_name = date('Ymd',strtotime($end_date));
?>

		<form action="" method="post">
			<label>Start Date: <input type="date" name="start_date" id="start_date" value="<?=date('Y-m-d',strtotime($start_date));?>"   /></label>
			<br>
			<label>End Date: <input type="date" name="end_date" id="end_date" value="<?=date('Y-m-d',strtotime($end_date));?>"   /></label>
			<br>
			<label>Referral Status: <input type="text" name="referral_status" id="referral_status" value="<?=$referral_status;?>"   /></label> pending | unpaid | paid | rejected
			<p><button type="submit" class="button button-large">Get Report</button><a id="downloadReport" type="button" class="button button-primary button-large" style="margin-left: 2em;">Download Report</a></p>
		</form>
		
		<table class="jb-affiliate-report" id="affiliateEarningReport">
		<tr><th>Affiliate</th><th>Aff ID</th><th>Referrals</th><th>Volume</th><th>Commissions</th><th>1% Override</th></tr>


<?php

	
/** Get Affiliates **/
	$affiliates_db = new Affiliate_WP_DB_Affiliates();
	$aff_args = array(
		'number'       => -1,
//		'offset'       => 0,
//		'exclude'      => array('1','252244'),
//		'user_id'      => 0,
//		'affiliate_id' => 0,
//		'status'       => '',
		'order'        => 'ASC',
		'orderby'      => 'affiliate_id',
		//'fields'       => '',
	);
	$affiliates = $affiliates_db->get_affiliates( $aff_args );
	foreach ( $affiliates as $affiliate ) {
		$affiliate_id = $affiliate->affiliate_id;
		
		/** Get Referrals **/

		$ref_args = array(
			'number'       => -1,
			//'offset'       => 0,
			//'referral_id'  => 0,
			//'payout_id'    => 0,
			'affiliate_id' => $affiliate_id,
			//'amount'       => 0,
			//'amount_compare' => '=',
			'date'         => array('start'=>$start_date,'end'=>$end_date),
			//'reference'    => '',
			//'context'      => '',
			//'campaign'     => '',
			'status'       => $referral_status,
			//'orderby'      => 'referral_id',
			//'order'        => 'DESC',
			//'search'       => false,
			//'fields'       => '',
		);
		$referral_db = new Affiliate_WP_Referrals_DB();
		$referrals = $referral_db->get_referrals( $ref_args );
		$ref_count = $referral_db->count($ref_args);
		
		$ref_number = 0;
		$ref_total = 0;
		$order_total = 0;
		foreach ( $referrals as $referral ) {
			$ref_number = $referral->referral_id;
			$order = wc_get_order($referral->reference);
			if ( $order ) {
				$order_vol = $order->get_total();
			} else {
				$order_vol = 0;
			}
			
			if ( $referral->status != 'rejected' ) {
				$ref_total = $ref_total + $referral->amount;
				$order_total = $order_total + $order_vol;
			}
		}
		?>
			<tr><td><a href="/wp-admin/admin.php?page=affiliate-wp-affiliates&affiliate_id=<?php echo $affiliate_id;?>&action=edit_affiliate" target="_blank"><?php echo $affiliates_db->get_affiliate_name($affiliate);?></a></td><td><?php echo $affiliate_id;?></td><td><a href="/wp-admin/admin.php?s&page=affiliate-wp-referrals&affiliate_id=<?php echo $affiliate_id;?>&filter_from=<?php echo $start_url;?>&filter_to=<?php echo $end_url;?>" target="_blank"><?php echo $ref_count;?></a></td><td><?php echo number_format($order_total, 2); ?></td><td><?php echo number_format($ref_total, 2); ?></td><td><?php echo number_format(($order_total*0.01),2); ?></td></tr>
		
		<?php
	}
echo "</table></div>";
?>

<script type='text/javascript'>
        jQuery(document).ready(function () {

            console.log("HELLO")
            function exportTableToCSV($table, filename) {
                var $headers = $table.find('tr:has(th)')
                    ,$rows = $table.find('tr:has(td)')

                    // Temporary delimiter characters unlikely to be typed by keyboard
                    // This is to avoid accidentally splitting the actual contents
                    ,tmpColDelim = String.fromCharCode(11) // vertical tab character
                    ,tmpRowDelim = String.fromCharCode(0) // null character

                    // actual delimiter characters for CSV format
                    ,colDelim = '","'
                    ,rowDelim = '"\r\n"';

                    // Grab text from table into CSV formatted string
                    var csv = '"';
                    csv += formatRows($headers.map(grabRow));
                    csv += rowDelim;
                    csv += formatRows($rows.map(grabRow)) + '"';

                    // Data URI
                    var csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);

                // For IE (tested 10+)
                if (window.navigator.msSaveOrOpenBlob) {
                    var blob = new Blob([decodeURIComponent(encodeURI(csv))], {
                        type: "text/csv;charset=utf-8;"
                    });
                    navigator.msSaveBlob(blob, filename);
                } else {
                    jQuery(this)
                        .attr({
                            'download': filename
                            ,'href': csvData
                            //,'target' : '_blank' //if you want it to open in a new window
                    });
                }

                //------------------------------------------------------------
                // Helper Functions 
                //------------------------------------------------------------
                // Format the output so it has the appropriate delimiters
                function formatRows(rows){
                    return rows.get().join(tmpRowDelim)
                        .split(tmpRowDelim).join(rowDelim)
                        .split(tmpColDelim).join(colDelim);
                }
                // Grab and format a row from the table
                function grabRow(i,row){
                     
                    var $row = jQuery(row);
                    //for some reason $cols = $row.find('td') || $row.find('th') won't work...
                    var $cols = $row.find('td'); 
                    if(!$cols.length) $cols = $row.find('th');  

                    return $cols.map(grabCol)
                                .get().join(tmpColDelim);
                }
                // Grab and format a column from the table 
                function grabCol(j,col){
                    var $col = jQuery(col),
                        $text = $col.text();

                    return $text.replace('"', '""'); // escape double quotes

                }
            }


            // This must be a hyperlink
            jQuery("#downloadReport").click(function (event) {
                var outputFile = "<?=get_bloginfo('name')?> affiliate earnings report <?=$start_name;?>-<?=$end_name;?>.csv";
                //var outputFile = window.prompt("What do you want to name your output file (Note: This won't have any effect on Safari)") || 'export';
                //outputFile = outputFile.replace('.csv','') + '.csv'
                 
                // CSV
                exportTableToCSV.apply(this, [jQuery('#affiliateEarningReport'), outputFile]);
                
                // IF CSV, don't do event.preventDefault() or return false
                // We actually need this to be a typical hyperlink
            });
        });
    </script>
	<?php
}

