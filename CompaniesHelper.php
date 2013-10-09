<?php
/**
 * Class CompaniesHelper
 *
 * This class is used on the company pages to retrieve
 * the various data files and fields
 */
class CompaniesHelper{

    /**
     * Get a list by companies based on parameters passed in, used for the menu filter
     * and search
     *
     * @param string $filterFirstCharacter
     * @param bool $matchStart
     * @param bool $searchAll
     * @param string $source
     * @return array
     */
    static function getCompanies($filterFirstCharacter='',$matchStart=true,$searchAll=false,$source='menu'){
		$companies = array();
		$file = Yii::app()->params['dataPath'].'/Company_Master.txt';
		$csvData = utf8_encode(file_get_contents($file));
		$rows = explode(PHP_EOL,$csvData);

		$companiesByCode = array();

		$headers = array();

		foreach( $rows as $key=>$value ){
			$csvDelim = ";";
			$data = str_getcsv($value, $csvDelim);
			if( $filterFirstCharacter!='' && !strstr($data[0],'<') ){
				if( $filterFirstCharacter=='#' ){
					if( preg_match("/[0-9]/",$data[1]) ){
						array_push($companies,$data);
					}
				}else{
					if( isSet($data[1]) ){
                        if( !$searchAll && preg_match("/".($matchStart ? '^' : '').$filterFirstCharacter."/",$data[1]) ){
                            array_push($companies,$data);
                        }else if( $searchAll ){
                            foreach( $data as $dataKey=>$dataValue ){
                                if( preg_match("/".($matchStart ? '^' : '').strtolower($filterFirstCharacter)."/",strtolower($dataValue)) ){
                                    array_push($companies,$data);
                                    break;
                                }
                            }
                        }
					}
				}
			}else if( $source=='menu' ){
				if( strstr($data[0],'<') ){
					foreach( $data as $header ){
						array_push($headers,strtolower(str_replace(' ','_',preg_replace("/>|</","",$header))));
					}
					Yii::app()->session['headers'] = $headers;
				}
				if( $filterFirstCharacter=='' ){
					array_push($companies,$data);
				}
			}
			if( $source=='menu' && isSet($data[0]) ) $companiesByCode[$data[0]] = $data;
		}

		if( $source=='menu' ) Yii::app()->session['companiesByCode'] = $companiesByCode;

		return $companies;
	}

    /**
     * Get a company Org Chart or false if not found
     *
     * @param $companyCode
     * @return bool|string
     */
    static function getOrgChart($companyCode){

        $file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$companyCode).'/Orgchart.pdf';

        if( file_exists($file) ){
            return 'Orgchart.pdf';
        }else{
            return false;
        }

        // Uncomment for previous version

		/*$file = Yii::app()->params['dataPath'].'/'.self::getParameter('ticker',$companyCode).'/Org_Chart.txt';

		if( file_exists($file) ){
			$csvData = file_get_contents($file);
			$orgChartFile = explode(PHP_EOL,$csvData);

			@mkdir(YiiBase::getPathOfAlias('webroot').'/orgcharts');

			$src = Yii::app()->params['dataPath']."/".self::getParameter('ticker',$companyCode)."/OrgChart";
			$dst = YiiBase::getPathOfAlias('webroot').'/orgcharts/'.self::getParameter('ticker',$companyCode);

			if( is_dir($src) ){

				if( !is_dir($dst) ) mkdir($dst);

				$getLastModDir = filemtime($src.'/.');
				$getLastModDir2 = filemtime($dst.'/.');

				if( $getLastModDir!=$getLastModDir2 ){

					$dir = opendir($src);
					@mkdir($dst);
					while( false !== ($file = readdir($dir)) ) {
						if(( $file!='.') && ($file!='..' )){
							if( is_dir($src.'/'.$file) ){
								recurse_copy($src.'/'.$file,$dst.'/'.$file);
							}
							else {
								copy($src.'/'.$file,$dst.'/'.$file);
							}
						}
					}
					closedir($dir);
				}
			}
			return str_replace(';','',$orgChartFile[1]);
		}else{
			return false;
		}*/

	}

    /**
     * Retrieve a company based on its unique code
     *
     * @param $companyCode
     * @return mixed
     */
    static function getCompanyByCode($companyCode){
		$companies = self::getCompanies('',false,true);

		return Yii::app()->session['companiesByCode'][$companyCode];
	}

    /**
     * Get the headers in the master file
     *
     * @return array
     */
    static function getHeaders(){
		$companies = array();
		$file = Yii::app()->params['dataPath'].'/Company_Master.txt';
		$csvData = utf8_encode(file_get_contents($file));
		$rows = explode(PHP_EOL,$csvData);

		$companiesByCode = array();

		foreach( $rows as $key=>$value ){
			$csvDelim = ";";
			$data = str_getcsv($value, $csvDelim);
			if( strstr($data[0],'<') ){
				$headers = array();
				foreach( $data as $header ){
					array_push($headers,preg_replace("/>|</","",$header));
				}
				break;
			}
		}

		return $headers;
	}

    /**
     * Get an address for the company
     *
     * @param $company
     * @param string $type
     * @param string $delim
     * @return string
     */
    static function getAddress($company,$type='address',$delim=', '){
		$address = '';
		for( $x=1; $x<5; $x++ ){
			$address .= self::getParameter($type.'_'.$x,$company)!='' ? self::getParameter($type.'_'.$x,$company).($x!=4 ? $delim : '') : '';
		}
		$address = preg_replace("/".$delim."$/","",$address);
		$address .= $delim.self::getParameter($type.'_code',$company);
		return $address;
	}

    /**
     * Get the logo for a company
     *
     * @param $company
     * @return bool|string
     */
    static function getLogo($company){
		// check if the logo exists
		if( file_exists(Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/logo.jpg' ) ){
			return Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/logo.jpg';
		}else{
			return false;
		}
	}

    /**
     * Get the nature of business for the company
     *
     * @param $company
     * @return bool|string
     */
    static function getNatureOfBusiness($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Nature_of_Business.txt';
		if( file_exists($file) ){
			$data = utf8_encode(file_get_contents($file));
		}else{
			return false;
		}

		return $data;
	}

    /**
     * Get the share price data for the company
     *
     * @param $company
     * @return array|bool
     */
    static function getSharePrice($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Price_Data.txt';
		$results = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);
				foreach( $data as $dataKey=>$dataValue ){
					if( $dataKey==0 ){
						$data[$dataKey] = strtotime(substr($dataValue,0,4).'-'.substr($dataValue,4,2).'-'.substr($dataValue,6,2))*1000;
						//$data[$dataKey] = getdate();
					}else{
						$data[$dataKey] = (float)$dataValue;
					}

				}

				array_push($results,$data);
			}
            $newDateArray = array();
            foreach( $results as $key=>$value ){
                array_push($newDateArray,$value[0]);
            }
            asort($newDateArray);
            $newDataResult = array();
            foreach( $newDateArray as $key=>$value ){
                array_push($newDataResult,$results[$key]);
            }
		}else{
			return false;
		}

		array_shift($newDataResult);
		array_pop($newDataResult);

		return $newDataResult;
	}

    /**
     * Get the financials for the company
     *
     * @param $company
     * @return array|bool
     */
    static function getFinancials($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Financials.txt';
		$results = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);
				array_push($results,$data);
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return $results;
	}

    /**
     * Get the shareholders for a company
     *
     * @param $company
     * @return array|bool
     */
    static function getShareholders($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Share_Holders.txt';
		$results = array();
		$resultsFormatted = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);
				array_push($results,$data);
				if( $key!=0 && isSet($data[1]) ){
					$resultsFormatted[$data[0]] = $data[1]=='' ? 0 : $data[1];
				}
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return array('results'=>$results,'resultsFormatted'=>$resultsFormatted);
	}

    /**
     * Get shareholder info by country
     *
     * @param $company
     * @return array|bool
     */
    static function getShareholdersCountry($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Share_Holders_By_Country.txt';
		$results = array();
		$resultsFormatted = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);
				array_push($results,$data);
				if( $key!=0 && isSet($data[1]) ){
					$resultsFormatted[$data[0]] = $data[1]=='' ? 0 : $data[1];
				}
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return array('results'=>$results,'resultsFormatted'=>$resultsFormatted);
	}

    /**
     * Get the directors dealings for a company
     * @param $company
     * @return array|bool
     */
    static function getDirectorsDealings($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Directors_Dealings.txt';
		$results = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);
				array_push($results,$data);
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return $results;
	}

    /**
     * Get the directors biographies
     *
     * @param $company
     * @return array|bool
     */
    static function getDirectorsBiography($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Directors.txt';
		$results = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);

				if( $key!=0 && isSet($data[0]) && $data[0]!='' ){
					$data[1] = utf8_encode($data[1]);
					// get their profiles
					$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Directors/'.$data[0];
					if( file_exists($file) ){
						$directorData = file_get_contents($file);
						$directorData = utf8_encode($directorData);
						array_push($data,$directorData);
					}
				}

				array_push($results,$data);
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return $results;
	}

    /**
     * Get the SENS articles for a company
     *
     * @param $company
     * @return array|bool
     */
    static function getSensArticles($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Sens_Articles.txt';
		$results = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);

				if( $key!=0 && isSet($data[0]) && $data[0]!='' ){
					$data[1] = utf8_encode($data[1]);
					// get their profiles
					$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Sens/'.$data[0];
					//$data[2] = str_replace('...','',str_replace('click for more','',$data[2]));
                    $data[2] = str_replace('click for more','',$data[2]);
					if( file_exists($file) ){
						$sensData = file_get_contents($file);
						$sensData = substr(utf8_encode($sensData),0,Yii::app()->params['sensArticleLimit']);
						array_push($data,nl2br($sensData));
                        array_push($data,str_replace('TXT','PDF',$data[0]));
					}
				}

				array_push($results,$data);
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return $results;
	}

    /**
     * Get the brands for a company
     *
     * @param $company
     * @return array|bool
     */
    static function getBrands($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Company_Brands.txt';
		$results = array();
		if( file_exists($file) ){
			$csvData = utf8_encode(file_get_contents($file));
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);
				array_push($results,$data);
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return $results;
	}

    /**
     * Get the BETA Analisys for a company
     *
     * @param $company
     * @return array|bool
     */
    static function getBetaAnalisys($company){
		$file = Yii::app()->params['dataPath'].'/'.self::getParameter('series_id',$company).'/Beta_Scatter_Data.txt';
		$results = array();
		if( file_exists($file) ){
			$csvData = file_get_contents($file);
			$rows = explode(PHP_EOL,$csvData);

			foreach( $rows as $key=>$value ){
				$csvDelim = ";";
				$data = str_getcsv($value, $csvDelim);
				array_push($results,$data);
			}
		}else{
			return false;
		}

		//array_shift($results);
		array_pop($results);

		return $results;
	}

    /**
     * Generic function to get a parameter from a company
     *
     * @param $param
     * @param $company
     * @param bool $ucwords
     * @return string
     */
    static function getParameter($param,$company,$ucwords=false){
		$headers = Yii::app()->session['headers'];
		return $ucwords ? utf8_encode(ucwords(strtolower($company[array_search($param,$headers)]))) : utf8_encode($company[array_search($param,$headers)]);
	}

}