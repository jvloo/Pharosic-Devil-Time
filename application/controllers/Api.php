<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

	public function __construct() {

		parent::__construct();
		header("Access-Control-Allow-Origin: *");
		header("Content-Type: application/json; charset=UTF-8");
	}

	public function index() {

		$output = array(
			'status'	=> '400',
			'code'		=> 'InvalidUri',
			'message'	=>	'The requested URI does not represent any resource on the server.'
		);

		print_r( json_encode($output) );
	}

	public function posts( $method = '' ) {

		if( $method === 'GET' ) {
			header("Access-Control-Allow-Methods: GET");

			//		GET specific post.
			//		/GET/id/ ${pid}
			if( isset($this->input->get('pid')) ) {



			//		GET all post.
			//		/GET/limit/ ${limit} /offset/ ${offset}
			} else {
				$table = 'posts';
				$query = 'SELECT * FROM ' . $table;

				if( isset($this->input->get('limit')) ) {
					$limit = $this->input->get('limit');
					$query .= ' LIMIT ' . $limit;
				}

				if( isset($this->input->get('offset')) ) {

					$offset = $this->input->get('offset');
					$query .= ' OFFSET ' . $offset;
				}


				print_r( json_encode($output) );
			}

		} elseif( $method === 'POST' ) {

		} else {

			$output = array(
				'status'	=> '400',
				'code'		=> 'ConditionHeadersNotSupported',
				'message'	=>	'Condition headers are not supported.'
			);

			print_r( json_encode($output) );
		}

		private function get_all( $table = '' ) {

			$result = $this->db->get($table)
												 ->result_array();

			return $result;
		}


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
}
