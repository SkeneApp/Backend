<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller {

  public function index() {
    // Silence is golden.
    exit;
  }

  public function add() {
    // FUCK MVC! I'm sticking with "VC" only
    $data = array(
      'latitude'  => $this->input->post('lat'),
      'longitude' => $this->input->post('long'),
      'text'      => $this->input->post('text'),
      'pubTime'   => time() + $this->input->post('pubt')
    );

    $this->db->insert('whispers', $data);
    // Return the ID of the newly inserted message
    echo $this->db->insert_id();
  }

  public function add_json() {
    $json = json_decode(trim(file_get_contents('php://input')));
    $data = array(
      'latitude'  => $json->latitude,
      'longitude' => $json->longitude,
      'text'      => $json->text,
      'pubTime'   => time() + $json->pubTime
    );

    $this->db->insert('whispers', $data);
    // Return the ID of the newly inserted message
    echo $this->db->insert_id();
  }

  public function get_servertime() {
    echo time();
  }

  public function get_latest($count = 200, $timestamp = '0') {
    if ($this->input->get('timestamp')) $timestamp = $this->input->get('timestamp');
    if ($this->input->get('count')) $count = $this->input->get('count');
    if ($this->input->get('req_id')) {
      // Request ID is not required, but if supplied, it is simply echoed back
      echo "{\"req_id\":\"". $this->input->get('req_id') ."\"}";
    }

    $this->db->start_cache();
    $query = $this->db->order_by("id", "desc")->get_where('whispers',
                                                          array('pubTime >' => $timestamp,
                                                                'pubTime <' => time()),
                                                          $count);
    $result = $query->result();
    $this->db->stop_cache();
    $code = '';
    $i = 0;

    if ($this->input->get('json')) { // mobile
      echo json_encode($result);
    } else {
      foreach($result as $whisper) {
        $code .= '<tr><td>'.$whisper->text.'</td>';
        if ($i <= $count) {
          $code .= '<td id="last_whisper">'.$whisper->pubTime.'</td></tr>';
        } else {
          $code .= '<td>'.$whisper->pubTime.'</td></tr>';
        }
        $i++;
      }

      echo $code;
    }
  }

  public function get_local_whispers($count = 50) {
    $timestamp = $this->input->get('timestamp') ? $this->input->get('timestamp') || 0;
    $min_lat = $this->input->get('min_lat');
    $max_lat = $this->input->get('max_lat');
    $min_long = $this->input->get('min_long');
    $max_long = $this->input->get('max_long');
    if ($this->input->get('req_id')) {
      // Request ID is not required, but if supplied, it is simply echoed back
      echo "{\"req_id\":\"". $this->input->get('req_id') ."\"}";
    }

    if ($this->input->get('newCode')) {
      /*
      ?newCode=1 is required in URL, implemented so that old code and programs will not break for now, later it can be removed
      radius in kilometers, lat, long required parameters for this section
      */
      $R = 6371;  // earth's mean radius, km

      // first-cut bounding box (in degrees)
      $max_lat = $lat + rad2deg($radius/$R);
      $min_lat = $lat - rad2deg($radius/$R);

      // compensate for degrees longitude getting smaller with increasing latitude
      $max_long = $long + rad2deg($radius/$R/cos(deg2rad($lat)));
      $min_long = $long - rad2deg($radius/$R/cos(deg2rad($lat)));
    }

    $query = $this->db->order_by("id", "desc")->get_where('whispers',
                                                          array('latitude >=' => $min_lat,
                                                                'latitude <=' => $max_lat,
                                                                'longitude >=' => $min_long,
                                                                'longitude <=' => $max_long,
                                                                'pubTime >=' => $timestamp,
                                                                'pubTime <=' => time()),
                                                          $count);

    $result = $query->result();
    $code = '';
    $i = 0;

    if ($this->input->get('json')) {
      echo json_encode($result);
    } else {
      foreach($result as $whisper) {
        $code .= '<tr><td>'.$whisper->text.'</td>';
        if ($i <= $count) {
          $code .= '<td id="last_whisper">'.$whisper->pubTime.'</td></tr>';
        } else {
          $code .= '<td>'.$whisper->pubTime.'</td></tr>';
        }
        $i++;
      }

      echo $code;
    }
  }
}
