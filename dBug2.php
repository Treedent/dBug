<?php
/*********************************************************************************************************************\
 * LAST UPDATE
 * ============
 *
 * June 18, 2020
 *  * AUTHOR
 * =============
 * Regis TEDONE
 * regis.tedone@gmail.com
 *
 *  PHP 7.X oop compatibility
 *  CSS And JavaScript modernisation
 *  Debug formats added: Json / Mysql / Numeric/ Null
 ***********************************************************************************
 *
 *
 *
 * March 22, 2007
 *
 *
 * AUTHOR
 * =============
 * Kwaku Otchere
 * ospinto@hotmail.com
 *
 * Thanks to Andrew Hewitt (rudebwoy@hotmail.com) for the idea and suggestion
 *
 * All the credit goes to ColdFusion's brilliant cfdump tag
 * Hope the next version of PHP can implement this or have something similar
 * I love PHP, but var_dump BLOWS!!!
 *
 * FOR DOCUMENTATION AND MORE EXAMPLES: VISIT http://dbug.ospinto.com
 *
 *
 * PURPOSE
 * =============
 * Dumps/Displays the contents of a variable in a colored tabular format
 * Based on the idea, javascript and css code of Macromedia's ColdFusion cfdump tag
 * A much better presentation of a variable's contents than PHP's var_dump and print_r functions
 *
 *
 * USAGE
 * =============
 * full example:
 * new dBug2 ( variable [,forceType] [,title] );
 * example:
 * new dBug2 ( $myVariable );
 *
 *
 * if the optional "forceType" string is given, the variable supplied to the
 * function is forced to have that forceType type.
 * example: new dBug( $myVariable , "array" );
 * will force $myVariable to be treated and dumped as an array type,
 * even though it might originally have been a string type, etc.
 *
 * NOTE!
 * ==============
 * forceType is REQUIRED for dumping an xml string or xml file
 * new dBug2 ( $strXml, "xml" );
 *
\*********************************************************************************************************************/
namespace SYRADEV\Dbg;

/**
 * Class dBug2
 * @package SYRADEV\Dbg
 */
class dBug2 {
	
	private $xmlCData;
	private $xmlSData;
	private $xmlDData;
	private $xmlCount=0;
	private $xmlAttrib;
	private $xmlName;
	private $arrType = [ 'array', 'object', 'resource', 'boolean', 'NULL' ];
	private $bInitialized = false;
	private $bCollapsed;
	private $arrHistory = [];
	
	/**
	 * dBug2 constructor.
	 *
	 * @param $var
	 * @param string $forceType
	 * @param bool $bCollapsed
	 */
	public function __construct($var, $forceType= '', $bCollapsed=false) {
		//include js and css scripts
		if(!defined('BDBUGINIT')) {
			define( 'BDBUGINIT', TRUE);
			$this->initJSandCSS();
		}
		$arrAccept=[ 'json', 'image', 'mysql', 'array', 'object', 'xml' ]; //array of variable types that can be "forced"
		$this->bCollapsed = $bCollapsed;
		if( in_array( $forceType, $arrAccept, true ) ) {
		    $this->{'varIs' . ucfirst( $forceType )}( $var );
		}
		else {
			$this->checkType( $var );
		}
	}

	//get variable name
	private function getVariableName() {
		$arrBacktrace = debug_backtrace();

		echo '<pre>';
		var_dump($arrBacktrace);
		echo '</pre>';

		// Possible 'included' functions
		$arrInclude = [ 'include', 'include_once', 'require', 'require_once' ];
		
		// Check for any included/required files. if found,
		// get array of the last included file (they contain the right line numbers)
		for($i=count($arrBacktrace)-1; $i>=0; $i--) {
			$arrCurrent = $arrBacktrace[$i];
			if( array_key_exists( 'function', $arrCurrent) &&
			    ( in_array( $arrCurrent['function'], $arrInclude, true ) || ( 0 !== strcasecmp($arrCurrent['function'], 'dbug2' )))) {
				continue;
			}
			$arrFile = $arrCurrent;
			break;
		}
		if(isset($arrFile)) {
			$arrLines = file($arrFile['file']);
			$code = $arrLines[( $arrFile['line'] - 1)];
			// Find call to dBug class
			preg_match('/\bnew dBug2\s*\(\s*(.+)\s*\);/i', $code, $arrMatches);
			
			return $arrMatches[1];
		}
		return '';
	}

	/**
	 * Create the main table header
	 *
	 * @param $type
	 * @param $header
	 * @param int $colspan
	 */
	private function makeTableHeader($type,$header,$colspan=2): void {
		if(!$this->bInitialized) {
			$header = ' (' . $header . ') ';
			$this->bInitialized = true;
		}
		$str_i = ($this->bCollapsed) ? 'style="font-style:italic" ' : '';
		
		echo '<table class="dBug_table dBug_'.$type.'">
				<tr>
					<th '.$str_i.' class="dBug_clickable_table dBug_' . $type . 'Header" colspan="' . $colspan . '">' . $header . '</th>
				</tr>';
	}

	/**
	 * Create the table row header
	 *
	 * @param $type
	 * @param $header
	 */
	private function makeTDHeader($type,$header): void {
		$str_d = ($this->bCollapsed) ? ' style="display:none"' : '';
		echo '<tr'.$str_d.'>
				<td class="dBug_clickable_row dBug_'.$type.'Key">' . $header . '</td>
				<td>';
	}
	
	//close table row
	private function closeTDRow(): string {
		return "</td></tr>\n";
	}
	
	//error
	private function error($type): string {
		$error= 'Error: Variable cannot be a';
		// this just checks if the type starts with a vowel or "x" and displays either "a" or "an"
		if(in_array(substr($type,0,1),array( 'a', 'e', 'i', 'o', 'u', 'x' ))) {
			$error .= 'n';
		}
		return ( $error . ' ' . $type . ' type' );
	}

	/**
	 * Check variable type
	 *
	 * @param $var
	 */
	private function checkType($var): void {
	    switch(gettype($var)) {
			case 'resource':
				$this->varIsResource($var);
				break;
			case 'object':
				$this->varIsObject($var, 'object');
				break;
			case 'array':
				$this->varIsArray($var);
				break;
			case 'NULL':
				$this->varIsNULL();
				break;
			case 'boolean':
				$this->varIsBoolean($var);
				break;
			case 'integer':
			case 'double':
				$this->varIsNumeric($var);
				break;
			default:
				$var=($var==='') ? ' [empty string] ' : $var;
				$strTable  = '<table class="dBug_table dBug_string"><tr><th class="dBug_clickable_table dBug_stringHeader">String</th></tr>'."\n";
				$strTable .= '<tr>' . "\n" . '<td>' . $var . '</td>' . "\n" . '</tr>' . "\n";
				if( $var !== ' [empty string] ' ) {
					$strTable .= '<tr>' . "\n" . '<th class="dBug_stringHeader">Lenght: ' . strlen( $var ) . '</th>' . "\n" . '</tr>' . "\n";
				}
				$strTable .= '</table>'."\n";
				echo $strTable;
				break;
		}
	}

    /**
     * If variable is JSON
     * @param string $json
     */
    private function varIsJson($json): void {
        $json_obj = json_decode($json);
        $this->varIsObject($json_obj, 'json');
    }

	/**
	 * If variable is a NULL type
	 */
	private function varIsNULL(): void {
		$nullTable = '<table class="dBug_table dBug_null">';
		$nullTable .= '<tr><td class="dBug_nullHeader">NULL Variable</td></tr>'."\n".'<tr>'."\n";
		$nullTable .= '</table>'."\n";
		echo $nullTable;
	}

	/**
	 * If variable is a numeric type
	 *
	 * @param $var
	 */
	private function varIsNumeric($var): void {
		$numericTable  = '<table class="dBug_table dBug_numeric">' . "\n" . '<tr>' . "\n" . '<th class="dBug_clickable_table dBug_numericHeader">' . gettype($var) . '</th>' . "\n" . '</tr>'."\n";
		$numericTable .= '<tr>' . "\n" . '<td>' . $var . '</td>' . "\n" . '</tr>' . "\n";
		$numericTable .= '</table>'."\n";
		echo $numericTable;
	}

    /**
     * If variable is a MySQL Result Object
     *
     * @param Object $Res
     */
    private function varIsMysql($Res): void {

            $numfields = $Res->field_count;
            $this->makeTableHeader( 'resource', 'MySQLi result');
            echo '<tr><td>';
            $this->makeTableHeader( 'resource', 'DATA', $numfields + 1);
            echo '<tr><th class="dBug_resourceKey">&nbsp;</th>';
            while ( $row = $Res->fetch_assoc() ) {
                foreach ( $row as $key => $value ) {
                    echo '<th class="dBug_resourceKey" title="'.$key.'">'.$key.'</th>';
                }
                break;
            }
            echo '</tr>';
            $Res->data_seek(0);
            $counter=1;
            while ( $row = $Res->fetch_assoc() ) {
                echo '<tr><td class="dBug_resourceKey">'.$counter.'</td>';
                foreach ($row as $key=>$value) {
                    switch($value) {
                        case '':
                            $fieldValue = '[<span class="red">empty string</span>]';
                            break;
                        case NULL:
                            $fieldValue = '[<span class="red">NULL</span>]';
                            break;
                        default:
                            $fieldValue = $value;
                            break;
                    }
                    echo '<td title="' . $key . '">' . $fieldValue . '</td>';
                }
                $counter++;
                echo '</tr>';
            }
            echo '</table>';
            echo '</td></tr>';
            echo '</table>';
    }


	/**
	 * If variable is a boolean type
	 *
	 * @param $var
	 */
	private function varIsBoolean($var): void {
		$var=($var===true) ? 'TRUE' : 'FALSE';
		$booleanTable  = '<table class="dBug_table dBug_boolean">' . "\n" . '<tr>' . "\n" . '<th class="dBug_clickable_table dBug_booleanHeader">Boolean</th>' . "\n" . '</tr>'."\n";
		$booleanTable .= '<tr>' . "\n" . '<td>' . $var . '</td>' . "\n" . '</tr>' . "\n";
		$booleanTable .= '</table>'."\n";
		echo $booleanTable;
	}
			
	/**
	 * If variable is an array type
	 *
	 * @param $var
	 */
	private function varIsArray($var): void {
		$var_ser            = serialize($var);
		$this->arrHistory[] = $var_ser;
		
		$this->makeTableHeader( 'array', 'array' );
		if(is_array($var)) {
			foreach($var as $key=>$value) {
				$this->makeTDHeader( 'array', $key);
				
				//check for recursion
				if(is_array($value)) {
					$var_ser = serialize($value);
					if(in_array($var_ser, $this->arrHistory, TRUE)) {
						$value = '*RECURSION*';
					}
				}
				
				if( in_array( gettype( $value ), $this->arrType, true ) ) {
					$this->checkType( $value );
				}
				else {
					$value=(trim($value)==='') ? '[empty string]' : $value;
					echo $value;
				}
				echo $this->closeTDRow();
			}
		}
		else {
			echo '<tr><td>' . $this->error( 'array' ) . $this->closeTDRow();
		}
		array_pop($this->arrHistory);
		echo '</table>';
	}


    /**
     * If variable is an image path
     *
     * @param string $img
     * @throws /Exception $e
     */
    private function varIsImage($img) {
        $fp = fopen($img, 'rb');
        $exif_data = @exif_read_data($fp);
        if(is_array($exif_data)) {
            $this->varIsArray($exif_data);
        } else {
            $this->checkType($img .  ' : No exif support for this image format!');
        }
        fclose($fp);
    }

    /**
     * If variable is an object type
     *
     * @param object $var
     * @param string $format
     */
	private function varIsObject($var, $format): void {
		$var_ser            = serialize($var);
		$this->arrHistory[] = $var_ser;
		$this->makeTableHeader( $format, $format );
		
		if(is_object($var)) {
			$arrObjVars=get_object_vars($var);
			foreach($arrObjVars as $key=>$value) {

				$value=(!is_object($value) && !is_array($value) && trim($value)==='') ? '[empty string]' : $value;
				$this->makeTDHeader( $format,$key);
				
				//check for recursion
				if(is_object($value)||is_array($value)) {
					$var_ser = serialize($value);
					if(in_array($var_ser, $this->arrHistory, TRUE)) {
						$value = (is_object($value)) ? '*RECURSION* -> $' . get_class($value) : '*RECURSION*';

					}
				}
				if( in_array( gettype( $value ), $this->arrType, true ) ) {
					$this->checkType( $value );
				}
				else {
					echo $value;
				}
				echo $this->closeTDRow();
			}
			$arrObjMethods=get_class_methods(get_class($var));
			foreach($arrObjMethods as $key=>$value) {
				$this->makeTDHeader( $format,$value);
				echo '[function]' . $this->closeTDRow();
			}
		}
		else {
			echo '<tr><td>' . $this->error( $format ) . $this->closeTDRow();
		}
		array_pop($this->arrHistory);
		echo '</table>';
	}

	// if variable is a resource type

	/**
	 * @param $var
	 */
	private function varIsResource($var): void {
		$this->makeTableHeader( 'resourceC', 'resource',1);
		echo "<tr>\n<td>\n";
		switch(get_resource_type($var)) {
			case 'pgsql result':
				$this->varIsDBResource($var);
				break;
			case 'gd':
				$this->varIsGDResource($var);
				break;
			case 'xml':
				$this->varIsXmlResource($var);
				break;
			default:
				echo get_resource_type($var).$this->closeTDRow();
				break;
		}
		echo $this->closeTDRow()."</table>\n";
	}

	/**
	 * If variable is a PostrgreSql database resource type
	 * 
	 * @param $var
	 * 
	 */
	private function varIsDBResource($var): void {
		$arrFields = ['name','type','flags'];
		$numrows = pg_num_rows($var);
		$numfields = pg_num_fields($var);
		$this->makeTableHeader( 'resource', 'PostgreSQL result', $numfields + 1);
		echo '<tr><td class="dBug_resourceKey">&nbsp;</td>';
		for($i=0;$i<$numfields;$i++) {
			$field_name = $field_header = '';
			foreach ( $arrFields as $j => $jValue ) {
				$db_func = 'pg_field_' . $jValue;
				if(function_exists($db_func)) {
					$fheader = $db_func( $var, $i ) . ' ';
					if($j===0) {
						$field_name = $fheader;
					}
					else {
						$field_header .= $fheader;
					}
				}
			}
			echo '<th class="dBug_resourceKey" title="'.$field_header.'">'.$field_name.'</th>';
		}
		echo '</tr>';
		for($i=0;$i<$numrows;$i++) {
			$row = pg_fetch_assoc($var);
			echo "<tr>\n";
			echo '<td class="dBug_resourceKey">'.($i+1).'</td>';
			foreach($row as $value) {
				$fieldrow = ($value==='') ? '[empty string]' : $value;
				echo '<td>' . $fieldrow . "</td>\n";
			}
			echo "</tr>\n";
		}
		echo '</table>';
		if($numrows>0) {
			pg_result_seek( $var, 0 );
		}
	}
	
	/**
	 * if variable is an image/gd resource type
	 *
	 * @param resource $imgRes
	 */
	private function varIsGDResource($imgRes): void {
	    $img_src_x = imagesx($imgRes);
        $img_src_y= imagesy($imgRes);
        $percent = 0.33;
        $img_dest_x = $img_src_x * $percent;
        $img_dest_y= $img_src_y * $percent;
        $im = @imagecreatetruecolor($img_dest_x, $img_dest_y);
        imagecopyresampled($im, $imgRes, 0, 0, 0, 0, $img_dest_x, $img_dest_y, $img_src_x, $img_src_y);
        $filename = './imgs/tmp/image-' . uniqid() . '.jpg';
        imagejpeg($im, $filename, 90);
	    $this->makeTableHeader( 'resource', 'gd',2);
        echo '<tr><td colspan="2">';
        echo '<img alt="img" src="'.$filename.'">';
        echo '</td></tr>';
		$this->makeTDHeader( 'resource', 'Width' );
		echo $img_src_x.$this->closeTDRow();
		$this->makeTDHeader( 'resource', 'Height' );
		echo $img_src_y.$this->closeTDRow();
		$this->makeTDHeader( 'resource', 'Colors' );
		echo imagecolorstotal($imgRes) === 0 ? 'TrueColor' : imagecolorstotal($imgRes) .$this->closeTDRow();
		echo '</table>';
	}
	
	/**
	 * if variable is an xml type
	 *
	 * @param mixed $var
	 */
	private function varIsXml($var) {
		$this->varIsXmlResource($var);
	}
	
	/**
	 * if variable is an xml resource type
	 *
	 * @param mixed $var
	 */
	private function varIsXmlResource($var):void {
		$xml_parser=xml_parser_create();
		xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0); 
		xml_set_element_handler($xml_parser,[&$this, 'xmlStartElement'], [&$this, 'xmlEndElement']);
		xml_set_character_data_handler($xml_parser,[&$this, 'xmlCharacterData']);
		xml_set_default_handler($xml_parser,[&$this, 'xmlDefaultHandler']);
		
		$this->makeTableHeader( 'xml', 'xml document',2);
		$this->makeTDHeader( 'xml', 'xmlRoot' );
		
		//attempt to open xml file
		$bFile=(!($fp=@fopen($var, 'rb' ))) ? false : true;
		
		//read xml file
		if($bFile) {
			while($data=str_replace("\n", '',fread($fp,4096))) {
				$this->xmlParse( $xml_parser, $data, feof( $fp ) );
			}
		}
		//if xml is not a file, attempt to read it as a string
		else {
			if(!is_string($var)) {
				echo $this->error( 'xml' ) . $this->closeTDRow() . "</table>\n";
				return;
			}
			$data=$var;
			$this->xmlParse($xml_parser,$data,1);
		}
		
		echo $this->closeTDRow()."</table>\n";
		
	}


	/**
	 * Parse xml
	 *
	 * @param $xml_parser
	 * @param $data
	 * @param $bFinal
	 */
	private function xmlParse($xml_parser,$data,$bFinal): void {
		if (!xml_parse($xml_parser,$data,$bFinal)) { 
				   die(sprintf("XML error: %s at line %d\n", 
							   xml_error_string(xml_get_error_code($xml_parser)), 
							   xml_get_current_line_number($xml_parser)));
		}
	}

	/**
	 * XML: inititiated when a start tag is encountered
	 *
	 * @param $parser
	 * @param $name
	 * @param $attribs
	 */
	private function xmlStartElement($parser, $name,$attribs): void {
		$this->xmlAttrib[$this->xmlCount]=$attribs;
		$this->xmlName[$this->xmlCount]=$name;
		$this->xmlSData[$this->xmlCount]='$this->makeTableHeader("xml","xml element",2);';
		$this->xmlSData[$this->xmlCount].='$this->makeTDHeader("xml","xmlName");';
		$this->xmlSData[$this->xmlCount].='echo "<strong>'.$this->xmlName[$this->xmlCount].'</strong>".$this->closeTDRow();';
		$this->xmlSData[$this->xmlCount].='$this->makeTDHeader("xml","xmlAttributes");';
		if(count($attribs)>0) {
			$this->xmlSData[ $this->xmlCount ] .= '$this->varIsArray($this->xmlAttrib[' . $this->xmlCount . ']);';
		}
		else {
			$this->xmlSData[ $this->xmlCount ] .= 'echo "&nbsp;";';
		}
		$this->xmlSData[$this->xmlCount].='echo $this->closeTDRow();';
		$this->xmlCount++;
	}

	/**
	 * Xml: initiated when an end tag is encountered
	 *
	 * @param $parser
	 * @param $name
	 */
	private function xmlEndElement($parser,$name): void {
		for($i=0;$i<$this->xmlCount;$i++) {
			eval($this->xmlSData[$i]);
			$this->makeTDHeader( 'xml', 'xmlText' );
			echo (!empty($this->xmlCData[$i])) ? $this->xmlCData[$i] : '&nbsp;';
			echo $this->closeTDRow();
			$this->makeTDHeader( 'xml', 'xmlComment' );
			echo (!empty($this->xmlDData[$i])) ? $this->xmlDData[$i] : '&nbsp;';
			echo $this->closeTDRow();
			$this->makeTDHeader( 'xml', 'xmlChildren' );
			unset($this->xmlCData[$i],$this->xmlDData[$i]);
		}
		echo $this->closeTDRow();
		echo '</table>';
		$this->xmlCount=0;
	} 

	/**
	 * Xml: initiated when text between tags is encountered
	 *
	 * @param $parser
	 * @param $data
	 */
	private function xmlCharacterData($parser,$data): void {
		$count=$this->xmlCount-1;
		if(!empty($this->xmlCData[$count])) {
			$this->xmlCData[ $count ] .= $data;
		}
		else {
			$this->xmlCData[ $count ] = $data;
		}
	}

	/**
	 * Xml: initiated when a comment or other miscellaneous texts is encountered
	 *
	 * @param $parser
	 * @param $data
	 */
	private function xmlDefaultHandler($parser,$data): void {
		//strip '<!--' and '-->' off comments
		$data=str_replace(array( '&lt;!--', '--&gt;' ), '',htmlspecialchars($data));
		$count=$this->xmlCount-1;
		if(!empty($this->xmlDData[$count])) {
			$this->xmlDData[ $count ] .= $data;
		}
		else {
			$this->xmlDData[ $count ] = $data;
		}
	}

	private function initJSandCSS(): void {
		echo <<<SCRIPTS
			
			<script>		
			    window.onload = () => {
			        const clickableTables = document.querySelectorAll('th.dBug_clickable_table');		        
					clickableTables.forEach(el => el.addEventListener('click', event => {
  						dBug_toggleTable(event.target);
					}));
			        const clickableRows = document.querySelectorAll('td.dBug_clickable_row');				
					clickableRows.forEach(el => el.addEventListener('click', event => {
					    dBug_toggleRow(event.target);
					}));
					function dBug_toggleRow(source) {
						let target = source.parentNode.lastChild;
						dBug_toggleTarget(target,dBug_toggleSource(source));
					}
					function dBug_toggleSource(source) {
						if (source.style.fontStyle==='italic') {
							source.style.fontStyle='normal';
							source.title='click to collapse';
							return 'open';
						} else {
							source.style.fontStyle='italic';
							source.title='click to expand';
							return 'closed';
						}
					}
					function dBug_toggleTarget(target,switchToState) {
						target.style.display = (switchToState==='open') ? '' : 'none';
					}
					function dBug_toggleTable(source) {
					    let switchToState=dBug_toggleSource(source);
						let table=source.parentNode.parentNode;
						for (let i=1;i<table.childNodes.length;i++) {
							let target=table.childNodes[i];
							if(target.style) {
								dBug_toggleTarget(target,switchToState);
							}
						}
					}
				};
			</script>
			
			<style>
                .red {
                    color:#F00
                }				
				table.dBug_string,
				table.dBug_numeric,
				table.dBug_boolean,
				table.dBug_array,
				table.dBug_object,
				table.dBug_json,
				table.dBug_resource,
				table.dBug_resourceC,
				table.dBug_xml { font-family:Verdana, Arial, Helvetica, sans-serif; color:#000; font-size:12px; border-spacing:2px; display:table; border-collapse:separate }
				.dBug_table { width: auto; border-spacing : 2px; border-collapse : collapse; border: 1px solid #666; margin-bottom: 5px }
				.dBug_table tr td { padding: 3px; vertical-align: top; line-height:1.3 }
				.dBug_stringHeader,
				.dBug_numericHeader,
				.dBug_booleanHeader,
				.dBug_arrayHeader,
				.dBug_objectHeader,
				.dBug_resourceHeader,
				.dBug_resourceCHeader,
				.dBug_xmlHeader { font-weight:bold; color:#fff }
				.dBug_clickable_table,
				.dBug_clickable_row { cursor:pointer }	
				/* String */
				table.dBug_string { background-color:#FE3 }
				table.dBug_string td { background-color:#fff }
				table.dBug_string th.dBug_stringHeader { background-color:#FB4 }
				/* Null */
				table.dBug_null td.dBug_nullHeader { color: #fff; background-color:#444 }
				/* Numeric */
				table.dBug_numeric { background-color:#F00 }
				table.dBug_numeric td { background-color:#fff }
				table.dBug_numeric th.dBug_numericHeader { background-color:#F34 }
				/* Boolean */
				table.dBug_boolean { background-color:#4F0 }
				table.dBug_boolean td { background-color:#fff }
				table.dBug_boolean th.dBug_booleanHeader { background-color:#7F0 }				
				/* Array */
				table.dBug_array { background-color:#060 }
				table.dBug_array td { background-color:#fff }
				table.dBug_array th.dBug_arrayHeader { background-color:#090 }
				table.dBug_array td.dBug_arrayKey { background-color:#cfc }
				/* Object */
				table.dBug_object { background-color:#00c }
				table.dBug_object td { background-color:#fff }
				table.dBug_object th.dBug_objectHeader { background-color:#44c }
				table.dBug_object td.dBug_objectKey { background-color:#cdf }
				/* Json */
				table.dBug_json { background-color:#125 }
				table.dBug_json td { background-color:#fff }
				table.dBug_json th.dBug_jsonHeader { color: #fff; background-color:#125 }
				table.dBug_json td.dBug_jsonKey { background-color:#cdf }				
				/* Resource */
				table.dBug_resource, table.dBug_resourceC { background-color:#848 }
				table.dBug_resource td, table.dBug_resourceC td { background-color:#fff }
				table.dBug_resource th.dBug_resourceHeader, table.dBug_resourceC th.dBug_resourceCHeader { background-color:#a6a; }
				table.dBug_resource td.dBug_resourceKey, table.dBug_resource th.dBug_resourceKey, table.dBug_resourceC td.dBug_resourceCKey, table.dBug_resourceC th.dBug_resourceCKey { background-color:#fdf; }				
				/* Xml */
				table.dBug_xml { background-color:#888 }
				table.dBug_xml td { background-color:#fff }
				table.dBug_xml th.dBug_xmlHeader { background-color:#aaa }
				table.dBug_xml td.dBug_xmlKey { background-color:#ddd }
			</style>
SCRIPTS;
	}
}