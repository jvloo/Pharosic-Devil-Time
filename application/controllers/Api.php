<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct() {

		parent::__construct();
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");
	}

	public function index() {

		$response = array(
			'status'	=> '400',
			'code'		=> 'InvalidUri',
			'message'	=>	'The requested URI does not represent any resource on the server.',
		);

		print_r( json_encode($response) );
	}

	public function posts( $method = '' ) {

		if( $method === 'GET' ) {

			header("Access-Control-Allow-Methods: GET");

			//		GET specific post.
			//		/GET/id/ ${pid}
			if( ! empty($this->input->get('pid')) ) {

				// *****TODO*****

			//		GET all post.
			//		/GET/limit/ ${limit} /offset/ ${offset}
			} else {

				$table = 'post';
				$query = 'SELECT * FROM ' . $table;

				if( ! empty($this->input->get('limit')) ) {
					$limit = $this->input->get('limit');
					$query = $query . ' LIMIT ' . $limit;
				}

				if( ! empty($this->input->get('offset')) ) {
					$offset = $this->input->get('offset');
					$query = $query . ' OFFSET ' . $offset;
				}

				$result = $this->db->query($query)->result_array();
				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource has been fetched and is transmitted in the message body.',
					'body'	=> $result,
				);

				print_r( json_encode($response) );
			}

		} elseif( $method === 'POST' ) {

			$error = false;

			$author_name = ! empty($this->input->post('author_name')) ? $this->input->post('author_name') : $error = true;
			$author_avatar = ! empty($this->input->post('author_avatar')) ? $this->input->post('author_avatar') : $error = true;
			$description = ! empty($this->input->post('description')) ? $this->input->post('description') : $error = true;
			$source = ! empty($this->input->post('source')) ? $this->input->post('source') : null;
			$quote_id = ! empty($this->input->post('quote_id')) ? $this->input->post('quote_id') : null;
			$user_id = ! empty($this->input->post('user_id')) ? $this->input->post('user_id') : $error = true;

			if( ! $error ) {

				$input = array(
					'author_name'		=>	$author_name,
					'author_avatar'	=>	$author_avatar,
					'description'		=>	$description,
					'source'				=>	$source,
					'quote_id'			=>	$quote_id,
					'is_approved'		=>	1,
					'created_on'		=>	date('Y-m-d H:i:s'),
				);

				$this->db->insert('post', $input);

				$input = array(
					$post_id = $this->db->insert_id(),
					$user_id = $user_id,
				);

				$this->db->insert('post_user', $input);

				$response = array(
					'status'	=> '200',
					'code'		=> 'OK',
					'message'	=>	'The resource describing the result of the action is transmitted in the message body.',
					'body'	=> $this->db->affected_rows(),
				);

				print_r( json_encode($response) );
			} else {

				$response = array(
					'status'	=> '400',
					'code'		=> 'InvalidQueryParameterValue',
					'message'	=>	'An invalid value was specified for one of the query parameters in the request URI.',
				);

				print_r( json_encode($response) );
			}

		} else {

			$output = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.',
			);

			print_r( json_encode($output) );
		}
	}

	public function user( $method = '' ) {

		if( $method === 'GET' ) {

			$error = false;

			//$bfp_hash = ! empty($this->input->get('bfp_hash')) ? $this->input->get('bfp_hash') : $error = true;
			//$bfp_components = ! empty($this->input->get('bfp_components')) ? $this->input->get('bfp_components') : $error = true;

			$bfp_hash = ''
			$bfp_components =

			$ip_address = file_get_contents('https://api.ipify.org/?format=json');
			$ip_components = file_get_contents('http://ip-api.com/json');

			if( ! $error ) {

				$exist_ip = $this->is_exist('ip_address', $ip_address);
				$exist_bfp = $this->is_exist('bfp_hash', $bfp_hash);

				// Both IP & BFP exist.
				if( $exist_ip && $exist_bfp ) {

					$bfp_id = $this->get_by('bfp_hash', $bfp_hash);
					$ip_id = $this->get_by('ip_address', $ip_address);

					$uid_by_ip = $this->get_uid_by('ip', $ip_address);
					$uid_by_bfp = $this->get_uid_by('bfp', $bfp_hash);

					// IP address pointed to single user.
					if( count($uid_by_ip) === 1 ) {


						// Both BFP and IP pointed to the same user.

					// IP address pointed to multiple user.
					} else {

					}


				// Either IP or BFP not exist.
				} elseif( ! $exist_ip || ! $exist_bfp ) {

					// Both IP & BFP not exist.
					if( ! $exist_ip && ! $exist_bfp ) {

						$input = array(
							'ip_address'		=>	$ip_address,
							'ip_components'	=>	$ip_components,
						);

						$this->mark_footprint('ip_address', $input);

						$input = array(
							'bfp_hash'		=>	$ip_address,
							'bfp_components'	=>	$ip_components,
						);

						$this->mark_footprint('bfp_hash', $input);



					// Only BFP exists.
					} elseif( ! $exist_ip && $exist_bfp ) {

					// Only IP exists.
					} else {

					}

				}

			} else {
				$output = array(
					'status'	=> '500',
					'code'		=> 'InternalError',
					'message'	=>	'The server encountered an internal error. Please retry the request.',
				);

				print_r( json_encode($output) );
			}

		} else {

			$output = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.',
			);

			print_r( json_encode($output) );
		}
	}

	// CHECK: Check if $object with $value exists in $table.
	private function is_exist( $object = '', $value = '', $table = '' ) {

		if( empty($table) ) {
			$table = $object;
		}

		$result = $this->db->where($object, $value)
											 ->get($table)
											 ->num_rows();

		if( $result > 0 ) {
			return true;
		} else {
			return false;
		}

	}


	// GET: Get all data under $table.
	private function get_all( $table = '' ) {

		$result = $this->db->get($table)
												 ->result_array();

		return $result;
	}

	// GET: Get data by $object with $value in $table.
	private function get_by( $object = '', $value = '', $table = '' ) {

		if( empty($table) ) {
			$table = $object;
		}

		$result = $this->db->where($object, $value)
											 ->get($table)
											 ->result_array();

		return $result;
	}

	//GET: Get user id by (bfp_hash|ip_address).
	private function get_uid_by( $method = '', $value = '' ) {

		switch( $method ) {
			case 'bfp':
				$object = 'bfp_hash';
				break;
			case 'ip':
				$object = 'ip_address';
				break;
		}

		$result = $this->db->where($object, $value)
											 ->get('user_' . $object)
											 ->result_array();

		return $result;
	}

	// INSERT: Mark a new footprint (IP address or Browser Fingerprint).
	private function mark_footprint( $table = '', $input = [] ) {

		$input['last_visit'] = date('Y-m-d H:i:s');
		$input['created_on'] = date('Y-m-d H:i:s');

		$this->db->insert($table, $input);
	}

	// INSERT: Link objects in $input and store in $table.
	private function link_together( $input = '', $table = '') {

		$this->db->insert($table, $input);
	}


	// Update.
	// Delete.

		/*
		$output = array(
			'status'	=> '400',
			'code'		=> 'InvalidUri',
			'message'	=>	'The requested URI does not represent any resource on the server.'
		);

		$output = array(
			'status'	=> '400',
			'code'		=> 'ConditionHeadersNotSupported',
			'message'	=>	'Condition headers are not supported.'
		);

		$output = array(
			'status'	=> '404',
			'code'		=> 'ResourceNotFound',
			'message'	=>	'The specified resource does not exist.'
		);

		header("Access-Control-Allow-Origin: *");
		header("Access-Control-Allow-Methods: GET");
		header("Content-Type: application/json; charset=UTF-8");
		*/
}
