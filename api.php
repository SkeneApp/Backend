<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

	public function index()
	{
		// Silence is golden.
		exit;
	}
	
	private function fixed_server_time()
	{
		// TODO: Ruben needs to fix the clock of the server, it's off by 14 min and 56 from UTC
		$server_time_utc_offset = (14 * 60 + 56);
		$fixed_time = (time() - $server_time_utc_offset);
		return $fixed_time;
	}
	
	public function server_time()
	{
		echo $this->fixed_server_time();
	}
	
	public function add()
	{
		$current_time = $this->fixed_server_time();
		$json = json_decode(trim(file_get_contents('php://input')));
		$data = array(
			'latitude' => $json->latitude,
			'longitude' => $json->longitude,
			'text' => $json->text,
			'pubTime' => $current_time + $json->pubDelay,
			'parent_id' => $json->parent_id
		);
		$this->db->insert('skene_messages', $data);
		// Return the ID of the newly inserted message
		echo $this->db->insert_id();
	}
	
	public function get()
	{
		// Available parameters:
		// lat: center location latitude
		// long: center location longitude
		// radius: radius of area in meters
		// timestamp (default: 0): get messages posted after this time. Format: Unix timestamp
		// count (default: 50): maximum number of messages to return
		// parent_id: (default 0): only get messages with this parent_id

		
		if ($this->input->get('timestamp')) {
			$timestamp = $this->input->get('timestamp');
		} else {
			$timestamp = 0;
		}
		if ($this->input->get('count')) {
			$count = $this->input->get('count');
		} else {
			$count = 50;
		}
		if ($this->input->get('parent_id')) {
			$parent_id = $this->input->get('parent_id');
		} else {
			$parent_id = 0;
			$radius = $this->input->get('radius') / 1000;
			$lat = $this->input->get('lat');
			$long = $this->input->get('long');

			// Get min/max latitude and longitue to select messages from the database
	        	$R = 6371;  // earth's mean radius, km
	        	// First-cut bounding box (in degrees)
	        	$max_lat = $lat + rad2deg($radius/$R);
	        	$min_lat = $lat - rad2deg($radius/$R);
	        	// Compensate for degrees longitude getting smaller with increasing latitude
	        	$max_long = $long + rad2deg($radius/$R/cos(deg2rad($lat)));
	        	$min_long = $long - rad2deg($radius/$R/cos(deg2rad($lat)));
		}
		
		$current_time = $this->fixed_server_time();

        	// Compose SQL query
        	if ($parent_id == 0) {
        		$query = $this->db->order_by("id", "desc")->get_where('skene_messages', array('parent_id = ' => 0, 'latitude >=' => $min_lat, 'latitude <=' => $max_lat, 'longitude >=' => $min_long, 'longitude <=' => $max_long, 'pubTime >=' => $timestamp, 'pubTime <=' => $current_time), $count);
        	} else {
        		$query = $this->db->order_by("id", "desc")->get_where('skene_messages', array('parent_id = ' => $parent_id, 'pubTime >=' => $timestamp, 'pubTime <=' => $current_time), $count);
        	}
        
        	// TODO: Filter out messages that are outside of defined radius
	
        	// Run database query
		$result = $query->result();

		// Output in JSON format
		echo json_encode($result);
	}
	
	public function get_map_data()
	{
		// Get N latest conversations.
		// TODO: Try to filter not by first message date, but by last reply date - tricky?

		// Available parameters:
		// min_lat: min/max lat/long of message
		// max_lat: --
		// min_long: --
		// max_long: --
		// timestamp (default: 0): get messages posted after this time. Format: Unix timestamp
		// count (default: 50): maximum number of messages to return

		// Get required parameters
		$min_lat = $this->input->get('min_lat');
		$max_lat = $this->input->get('max_lat');
		$min_long = $this->input->get('min_long');
		$max_long = $this->input->get('max_long');

		// Get optional parameters
		if ($this->input->get('count')) {
			$count = $this->input->get('count');
		} else {
			$count = 50;
		}
		if ($this->input->get('timestamp')) {
			$timestamp = $this->input->get('timestamp');
		} else {
			$timestamp = 0;
		}

		$current_time = $this->fixed_server_time();

		// Compose SQL query
		$query = $this->db->order_by("id", "desc")->select('latitude, longitude, pubTime')->get_where('skene_messages', array('parent_id = ' => 0, 'latitude >=' => $min_lat, 'latitude <=' => $max_lat, 'longitude >=' => $min_long, 'longitude <=' => $max_long, 'pubTime >=' => $timestamp, 'pubTime <=' => $current_time), $count);

        	// Run database query
		$result = $query->result();

		// Output in JSON format
		echo json_encode($result);
	}
}
