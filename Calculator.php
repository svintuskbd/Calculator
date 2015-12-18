<?php

/*
* The class takes the following parameters:
* @ $curency (string) points currency
* @ $start_min (int) minimum mileage involved in rendering
* @ $finish (int) the maximum number of kilometers involved in rendering
* @ $cost_one_km (int) the cost per kilometer
* @ $cost_3km (int) lowest fare for the minimum distance
* @ $lang (string) 
* 
* Form to send data relating the start and finish of the route should have the following margin settings <INPUT>.
* Example from <input name="calc_form[from][origin_street]"... 
* Example to <input name="calc_form[to][destination_street]"... 
* Example date <input name="calc_form[date_time][date]"... 
* Example time <input name="calc_form[date_time][time]"... 
*
* for this class of component must be installed php - curl
*
* author: Voytovich Oleg
*/

class Calculator
{
	protected $matrix_tarif = [];
	protected $start_min;
	protected $finish;
	protected $cost_one_km;
	protected $cost_3km;
	protected $currency;

	public function __construct($currency = 'euro', $lang = 'en-EN', $start_min = 3, $finish = 52, $cost_one_km = 2.17, $cost_3km = 9.46)
	{
		$this->start_min = $start_min;
		$this->lang = $lang;
		$this->finish = $finish;
		$this->cost_one_km = $cost_one_km;
		$this->cost_3km = $cost_3km;
		$this->currency = $currency;

		self::setMatrixTarif();
	}

	/*
	* The method establishes a matrix of tariffs from minimum to maximum value. 
	* The input accepts nothing.
	*/
	public function setMatrixTarif()
	{
		$matrix_tarif = [];
		for ($i = $this->start_min; $i <= $this->finish; $i++) { 
			if ($i == $this->start_min) {
				$matrix_tarif[$i] = $this->cost_3km;
			} else {
				$matrix_tarif[$i] = $matrix_tarif[$i - 1] + $this->cost_one_km;
			}
		}

		return $this->matrix_tarif = $matrix_tarif;
	}

	/*
	*The method determines the average arrival time according to the regulations of traffic rules, 
	* the distance from point A to point B, and the cost of travel, according to the established tariff.
	*/
	public function getInfoToTravel($req)
	{
		$price_of_travel = [];

		if ($req) {
			$from = '';
			$to = '';

			$options = array(
		        CURLOPT_RETURNTRANSFER => true,
		        CURLOPT_HTTPHEADER => array('Content-type: application/json') ,
					CURLOPT_USERAGENT => "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)",
		    );

			$from_arr = $req['calc_form']['from'];
			$to_arr = $req['calc_form']['to'];

			foreach ($from_arr as $key => $value) {
				$from = $from.'+'.$value;
			}
			$from = explode(' ', $from);
			$from = implode('+', $from);

			foreach ($to_arr as $key => $value) {
				$to = $to.'+'.$value;
			}
			$to = explode(' ', $to);
			$to = implode('+', $to);

			$tuCurl = curl_init();
			$params = 'origins='.$from.'&destinations='.$to.'&mode=driving&language='.$lang.'&key='.$api_key;
			curl_setopt($tuCurl, CURLOPT_URL, 'https://maps.googleapis.com/maps/api/distancematrix/json?'.$params);
			curl_setopt_array( $tuCurl, $options );
			$tuData = curl_exec($tuCurl);
			curl_close($tuCurl); 
			$json = json_decode($tuData);

			$distance = $json->rows[0]->elements[0]->distance;
			$duration = $json->rows[0]->elements[0]->duration;

			$price_of_travel['from'] = $json->origin_addresses[0];
			$price_of_travel['to'] = $json->destination_addresses[0];
			$price_of_travel['date'] = $req['calc_form']['date_time']['date'];
			$price_of_travel['time'] = $req['calc_form']['date_time']['time'];
			$distans_fin_result = round($distance->text, 0, PHP_ROUND_HALF_UP);
			$price_of_travel['duration'] = $duration->text;
			$price_of_travel['distance'] = $distans_fin_result.' km';

			foreach ($this->matrix_tarif as $key => $value) {
				if ($key == $distans_fin_result) {
					$price_of_travel['price_of_travel'] = $value.' '.$currency;
				} elseif ($distans_fin_result < $this->start_min) {
					$price_of_travel['price_of_travel'] = $this->cost_3km.' '.$this->currency;
				} elseif ($distans_fin_result > $this->finish) {
					$price_of_travel['price_of_travel'] = 'go too far';
				}
			}

		} else {
			$price_of_travel = 'Error';
		}

		return $price_of_travel;
	}
}