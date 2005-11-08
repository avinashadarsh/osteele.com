<?php
if (isset($table_prefix))
	define ("sk2_kLogTable", $table_prefix . "sk2_logs");
else
	define ("sk2_kLogTable", sk2_table_prefix . "sk2_logs");

global $sk2_log;
if (! isset($sk2_log))
	$sk2_log = new sk2_log;

global $sk2_settings;
if (! isset($sk2_settings))
	$sk2_settings = new sk2_settings;

$sk2_log->db_threshold = $sk2_settings->get_core_settings("log_threshold");

class sk2_settings
{
	var $core_settings;
	var $plugins_settings;
	var $stats;
	var $need_to_save = false;
	
	var $core_defaults = array("general_bias" => array("auto_draw" => true, "type" => "menu", "value" => 0, "caption" => "Severity: ", "options" => array(-2 => "Total Beeatch", -1 => "Kinda Mean", 0 => "Normal", 1 => "Nice", 2 => "Lovey Dovey")), 
											"log_threshold" => array("auto_draw" => true, "advanced" => true, "type" => "text", "value" => 4, "size" => 2, "caption" => "Only write logs level ", "after" => " and above <em>(logs with a level under this threshold will be displayed in the log dump if they are issued when using the Admin tools, but not written to the database)</em>."),
											"max_attempts" => array("auto_draw" => true, "advanced" => true, "type" => "text", "value" => 5, "size" => 3, "caption" => "How many total backup attempts should be allowed:", "after" => " <i>(you need to have backup methods, such as captcha, enabled for this to be taken in account).</i>"),
											// no default UI:
											
											"auto_purge_spamlist" => array("value" => false, "type" => "checkbox"),
											"purge_spamlist_duration" => array("value" => 30, "type" => "text", "size" => 3),
											"purge_spamlist_unit" => array("value" => "DAY", "type" => "menu", "options" => array("DAY" => "days", "HOUR" => "hours", "MINUTE" => "minutes")),

											"auto_purge_blacklist" => array("value" => true, "type" => "checkbox"),
											"purge_blacklist_duration" => array("value" => 30, "type" => "text", "size" => 3),
											"purge_blacklist_unit" => array("value" => "DAY", "type" => "menu", "options" => array("DAY" => "days", "HOUR" => "hours", "MINUTE" => "minutes")),
											"purge_blacklist_score" => array("value" => 99, "type" => "text", "size" => 3),
											"purge_blacklist_criterion" => array("value" => "last_used", "type" => "menu", "options" => array("added" => "added", "last_used" => "last used")),

											"auto_purge_logs" => array("value" => true, "type" => "checkbox"),
											"purge_logs_duration" => array("value" => 7, "type" => "text", "size" => 3),
											"purge_logs_unit" => array("value" => "DAY", "type" => "menu", "options" => array("DAY" => "days", "HOUR" => "hours", "MINUTE" => "minutes")),
											"purge_logs_level" => array("value" => 8, "type" => "text", "size" => 3),
											
	);
	
	function sk2_settings ()
	{
		$this->refresh_settings();
	}
	
	function refresh_settings()
	{
		foreach (array("plugins_settings", "core_settings", "stats") as $this_group)
		{
			$this->$this_group = get_settings("sk2_" . $this_group);
			if (! is_array($this->$this_group))
				$this->$this_group = array();
		}
	}
	
	function save_settings()
	{
		if (! $this->need_to_save)
			return;
		$this->need_to_save = false;
		$this->log_msg("Saved all settings to DB.",  1);

		update_option("sk2_core_settings", $this->core_settings);
		update_option("sk2_plugins_settings", $this->plugins_settings);
		update_option("sk2_stats", $this->stats);
	}

	function get_core_settings($section = 0)
	{ 
		if ($section)
		{
			if(isset($this->core_settings[$section]))
				return $this->core_settings[$section];
			else
				return $this->core_defaults[$section]['value'];
		}
		else
			return $this->core_settings;
	}
		
	function get_plugin_settings($plugin)
		{ return $this->plugins_settings[$plugin]; }
		
	function get_stats($section = 0)
	{
		if ($section)
			return @$this->stats[$section]; 
		else
			return @$this->stats; 
	}
	
	function increment_stats($section, $value = 1)
	{
		$this->need_to_save = true;
		$this->stats[$section] = (int) @$this->stats[$section] + $value;
	}
	
	function set_core_settings ($settings, $section = 0)
	{
		$this->need_to_save = true;
		if ($section)
			$this->core_settings[$section] = $settings;
		else
			$this->core_settings = $settings; 
	}
	
	function set_plugin_settings ($plugin_settings, $plugin_name)
	{
		$this->need_to_save = true;
		$this->plugins_settings[$plugin_name] = $plugin_settings; 
	}
		
	function set_plugins_settings ($plugins_settings)
	{
		$this->need_to_save = true;
		$this->plugins_settings = $plugins_settings; 
	}
		
	function set_stats ($stats, $section = 0)
	{ 
			$this->need_to_save = true;
			if($section)
				$this->stats['section'] = $stats;
			else
				$this->stats = $stats;
	}

	function log_msg($msg, $level = 0)
	{
		global $sk2_log;
		$sk2_log->log_msg($msg, $level, 0, 'sk2_settings');
	}


}

class sk2_log
{
	var $logs = array();
	var $db_threshold;
	var $live_threshold;
	var $live_output = true;
	
	function sk2_log ($db_threshold = 5, $live_threshold  = 7)
	{
		global $wpdb;
		
		$wpdb->hide_errors();
		$this->db_threshold = $db_threshold;
		$this->live_threshold = $live_threshold;
	}
	
	function log_msg($msg, $level = 0, $comment_id = 0, $component = "", $live = false, $div_wrapper = true)
	{
		global $wpdb;
		
		if ($this->live_output && ($level >= $this->live_threshold || $live))
		{
			if ($div_wrapper)
				echo "<div class=\"wrap sk_first\">\n";
			echo "<div class=\"sk2_log sk_level_$level\">" . $msg . "</div>";
			if ($div_wrapper)
				echo "</div>";
			$echoed = true;
		}
		else
			$echoed = false;

		$this->logs[] = array($msg, $level, $comment_id, time(), $echoed);
		
		if ($level >= $this->db_threshold)
			@$wpdb->query("INSERT INTO `". sk2_kLogTable ."` SET `msg` = '" . sk2_escape_string($msg) . "', `component` = '" . sk2_escape_string($component) . "', `level` = $level, `ts` = NOW()" );

	}

	function log_msg_mysql($msg, $level = 0, $comment_id = 0, $component = "")
	{
		$msg .= "<br/>\n" . __("SQL error: ") . "<code>". mysql_error() . "</code>";
		$this->log_msg($msg, $level, $comment_id, $component);
	}
		
	function dump_logs($threshold = 0)
	{
		foreach ($this->logs as $log)
			if ($log[1] >= $threshold)
				echo "<div class=\"sk2_log sk_level_$log[1]\">" . $log[3] . " - " . $log[0] . "</div>\n";
	}
	
	function echo_logs($threshold = 0)
	{
		$output = "";
		foreach ($this->logs as $log)
			if (! $log[4] && ($log[1] >= $threshold))
					$output .= "<div class=\"sk2_log sk_level_$log[1]\">" . $log[3] . " - " . $log[0] . "</div>\n";
		if ($output)
			echo "<div class=\"wrap sk_first\">\n$output<div>";
	}
}


?>