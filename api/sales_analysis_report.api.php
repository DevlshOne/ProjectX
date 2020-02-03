<?

##
## NEW API FILE FOR GENERATING SALES ANALYSIS REPORT DATA
## USED BY GUI AND API GET REQUEST 'sales_analysis_report'
##


class API_Sales_Analysis_Report{


	function handleAPI() {

		## HANDLE SALES ANALYSIS REPORT OUTPUT BASED ON QUERYSTRINGS

		## QUERYSTRING CHECK (THE FOLLOWING FIELDS ARE REQUIRED)
		if (isset($_REQUEST['stime']) &&
			isset($_REQUEST['etime']) &&
			isset($_REQUEST['campaign_code']) &&
			isset($_REQUEST['agent_cluster_id']) &&
			isset($_REQUEST['user_team_id']) &&
			isset($_REQUEST['combine_users']) &&
			isset($_REQUEST['user_group']) &&
			isset($_REQUEST['ignore_group']) &&
			isset($_REQUEST['vici_campaign_code']) &&
			isset($_REQUEST['vici_campaign_id'])) {

			## GET SALES ANALYSIS CLASS FOR GENERATE DATA FUNCTION
			include_once("../classes/sales_analysis.inc.php");

			## OUTPUT REPORT DATA BASED ON API MODE AND QUERY STRINGS
			echo $_SESSION['sales_analysis']->generateData($_REQUEST['stime'],$_REQUEST['etime'],$_REQUEST['campaign_code'],$_REQUEST['agent_cluster_id'],$_REQUEST['user_team_id'],$_REQUEST['combine_users'],$_REQUEST['user_group'],$_REQUEST['ignore_group'],$_REQUEST['vici_campaign_code'],NULL,$_REQUEST['vici_campaign_id'],$_SESSION['api']->mode);

		} else {

			## NOT ALL QUERY STRINGS PROVIDED
			echo "Invalid query strings provided.";

		}


	}
    

	}
