<?php

/**
 * @file
 * @author Michael Sypolt <msypolt@transitguru.info>
 * 
 * This file includes testing functions, not usually to be run on deployment!
 */

/**
 * Prints and array into expandable links using the JS toggle_hide()
 * 
 * @param array $array Array to be printed
 * @param string $prefix ID prefix to prevent multiple test prints from breaking
 * @param int $n Number to make each UL have unique identifier
 * @return int Running total of $n so that all IDs are unique
 */
function lwt_test_array_print($array, $prefix = 'foo', $n=0){
  if ($n >= 1){
    $hide = 'class="hide"';
  }
  else{
    $hide = '';
  }
  echo '<ul id="'.$prefix.'_'.$n.'" '.$hide.'>'."\n";
  foreach ($array as $key => $value){
    $n++;
    echo '<li><a href="javascript:;" onclick="toggle_hide(\''.$prefix.'_'.$n.'\');">'.$key.'</a> ';
    if (is_array($value)){
      $n = lwt_test_array_print($value, $prefix, $n);
    }
    else{
      echo $value;
    }
    echo "</li>\n";
  }
  echo "</ul>\n";
  return $n;
}

function lwt_test_showtime($begin, $end){
  $time = $end - $begin;
  echo $time * 1000 . 'ms';
}

/**
 * Tests the lwt_validate_inputs() function
 * 
 *  0 = no error
 * 11 = Empty value
 * 12 = String too long
 * 21 = Does not match regex
 * 41 = Line breaks/tabs in password
 * 42 = Line breaks in oneline input
 * 43 = Invalid email address
 * 44 = Special characters in input
 * 51 = Does not match date format
 * 52 = Date format good, but date itself is invalid
 * 61 = Not an integer
 * 62 = Not a number
 * 63 = Value less than or equal to minimum
 * 64 = Value less than minimum
 * 65 = Value greater than or equal to maximum
 * 66 = Value greater than maximum
 * 67 = Value does not match resolution (too precise)
 * 
 * @param array $inputs inputs that correspond with inputs for lwt_validate_inputs()
 * @param int $error Expected error number that should be returned
 * 
 * 
 */
function lwt_test_validation($inputs, $error=0){
  $result = call_user_func_array('lwt_validate_inputs', $inputs);
  if($result['error'] != $error){
    $status['message'] = "Failure, found {$result['error']}, expected {$error}";
    $status['error'] = 1;
  }
  else{
    $status['message'] = "Success";
    $status['error'] = 0;
  }
  return $status;
}

/**
 * Batch-runs tests of lwt_test_validation()
 * 
 * 
 */
function lwt_test_runvalidation(){
  $types = array('preg','memo','text','date','num');
  $formats = array(
    'preg' => array('/[0-9a-zA-Z]*/','/[0-9a-z]*/','/[a-z][0-9a-z]*/' ),
    'memo' => array('all', 'noscript', 'somehtml', 'nohtml', 'htmlencode'),
    'text' => array('password','oneline','email','nowacky','multiline'),
    'date' => array('Y-m-d H:i:s', 'm/d/Y H:i'),
    'num' => array('int','dec'),
  );
  $ranges = array( 
    array(0,4,1),
    array(-2,3,0.5),
    array(0,1,NULL),
  );
  $range_flags = array(
    array(TRUE, TRUE),
    array(TRUE, TRUE, TRUE),
  );
  
  //Test for empty string issues and numchars
  
  //lwt_validate_inputs($input, $type, $format, $required=false, $chars=NULL, $notrim=false, $range=array(null, null, null), $range_flags=array(false, false, false))
  
  echo "\n\nTesting 10 series (input) errors\n\n";
  $error = 0;
  $tests = array(
    array(array('', 'preg', $formats['preg'][0]),0),
    array(array('', 'memo', $formats['memo'][0]),0),
    array(array('', 'text', $formats['text'][0]),0),
    array(array('', 'date', $formats['date'][0]),0),
    array(array('', 'num', $formats['num'][0]),0),
    array(array('', 'num', $formats['num'][1]),0),
    array(array('', 'preg', $formats['preg'][0],  true, 12),11),
    array(array('', 'memo', $formats['memo'][0],  true, 12),11),
    array(array('', 'text', $formats['text'][0],  true, 12),11),
    array(array('', 'date', $formats['date'][0],  true, 12),11),
    array(array('', 'num', $formats['num'][0],  true, 12),11),
    array(array('', 'num', $formats['num'][1],  true, 12),11),
    array(array('Ab', 'preg', $formats['preg'][0],  true, 12),0),
    array(array('This', 'memo', $formats['memo'][0],  true, 12),0),
    array(array('Text', 'text', $formats['text'][0],  true, 12),0),
    array(array('2013-01-01 00:00:00', 'date', $formats['date'][0],  true, 22),0),
    array(array('23', 'num', $formats['num'][0],  true, 12),0),
    array(array('4.5', 'num', $formats['num'][1],  true, 12),0),
    array(array('Abdsfasdgfsdagadgd', 'preg', $formats['preg'][0],  true, 12),12),
    array(array('This is a Lot of text!!!!', 'memo', $formats['memo'][0],  true, 12),12),
    array(array('Text is a Lot of text', 'text', $formats['text'][0],  true, 12),12),
    array(array('2013-01-01 00:00:00', 'date', $formats['date'][0],  true, 4),12),
    array(array('2334543543', 'num', $formats['num'][0],  true, 4),12),
    array(array('4.54354364564', 'num', $formats['num'][1],  true, 4),12),
  );
  $num = count($tests);
  foreach ($tests as $test){
    $result = call_user_func_array('lwt_test_validation',$test);
    echo $result['message'] . "\n";
    $error += $result['error'];
  }
  echo "Tests ran with {$error}/{$num} errors.\n";
  
  echo "\n\nTesting 20 series (regex) errors\n\n";
  $error = 0;
  $tests = array(
    array(array('The_', 'preg', $formats['preg'][0]),21),
    array(array('foo', 'preg', $formats['preg'][0]),0),
    array(array('The&', 'preg', $formats['preg'][0]),21),
    array(array('2356.', 'preg', $formats['preg'][0]),0),
    array(array('Tjkhaeh', 'preg', $formats['preg'][0]),0),
    array(array('iwhekjdjfkj5t', 'preg', $formats['preg'][0]),0),
    array(array('iwhekjdjfkj5t', 'preg', $formats['preg'][1]),0),
    array(array('TheCaps32', 'preg', $formats['preg'][1]),21),
    array(array('thecaps32', 'preg', $formats['preg'][1]),0),
    array(array('a909098', 'preg', $formats['preg'][2]),0),
    array(array('A909098', 'preg', $formats['preg'][2]),21),
    array(array('9adfs09098', 'preg', $formats['preg'][2]),21),
  );
  $num = count($tests);
  foreach ($tests as $test){
    $result = call_user_func_array('lwt_test_validation',$test);
    echo $result['message'] . "\n";
    $error += $result['error'];
  }
  echo "Tests ran with {$error}/{$num} errors.\n";

  
  echo "\n\nTesting 40 series (string) errors\n\n";
  $error = 0;
  $tests = array(
    array(array('Password', 'text', $formats['text'][0]),0),
    array(array("Pass38y45e8uydkjh4\t", 'text', $formats['text'][0]),0),
    array(array("Pass38y45e8uydkjh4\tsdfsdd", 'text', $formats['text'][0]),41),
    array(array("Pass38y45e8uydkjh4\rjhkjhkj", 'text', $formats['text'][0]),41),
    array(array("Pass38y45e8uydkjh4\n", 'text', $formats['text'][0]),0),
    array(array("Pass38y45e8uydkjh4\ndsd", 'text', $formats['text'][0]),41),
    array(array("Pass38y45e8uydkjh4\n", 'text', $formats['text'][1]),0),
    array(array("Pass38y45e8uydkjh4\nsd", 'text', $formats['text'][1]),42),
    array(array("Pass38y45e8uydkjh4\rdsfds", 'text', $formats['text'][1]),42),
    array(array("Text with one\tline", 'text', $formats['text'][1]),0),
    array(array("Text with one line", 'text', $formats['text'][1]),0),
    array(array("foo@dev.null", 'text', $formats['text'][2]),0),
    array(array("foo@dev", 'text', $formats['text'][2]),43),
    array(array("foo.dev@null", 'text', $formats['text'][2]),43),
    array(array("someone.someone@example.com", 'text', $formats['text'][2]),0),
    array(array("someone-someone@example.com", 'text', $formats['text'][2]),0),
    array(array("someone+someone@example.com", 'text', $formats['text'][2]),0),
    array(array("someone_someone@example.com", 'text', $formats['text'][2]),0),
    array(array("some989yinmhg", 'text', $formats['text'][3]),0),
    array(array("%6&dd>", 'text', $formats['text'][3]),44),
    
  );
  $num = count($tests);
  foreach ($tests as $test){
    $result = call_user_func_array('lwt_test_validation',$test);
    echo $result['message'] . "\n";
    $error += $result['error'];
  }
  echo "Tests ran with {$error}/{$num} errors.\n";

  echo "\n\nTesting 50 series (date) errors\n\n";
  $error = 0;
  $tests = array(
    array(array('2014-01-01 04:00:00', 'date', $formats['date'][0]),0),
    array(array('2014-01-01 04:00:00', 'date', $formats['date'][0]),0),
    array(array('2014-01-01 04:00:60', 'date', $formats['date'][0]),52),
    array(array('2014-01-01 24:00:00', 'date', $formats['date'][0]),52),
    array(array('2013-12-32 04:00:00', 'date', $formats['date'][0]),52),
    array(array('12/26/1981 17:13', 'date', $formats['date'][0]),51),
    array(array('01/26/1981 17:13', 'date', $formats['date'][0]),51),
    array(array('12/26/1981 17:13', 'date', $formats['date'][1]),0),
    array(array('2014-01-01 04:00:00', 'date', $formats['date'][1]),51),
    array(array('2014-01-01 04:00:00', 'date', $formats['date'][1]),51),
    array(array('12/26/1981 27:13', 'date', $formats['date'][1]),52),
    array(array('81/26/1981 17:13', 'date', $formats['date'][1]),52),
    
  );
  $num = count($tests);
  foreach ($tests as $test){
    $result = call_user_func_array('lwt_test_validation',$test);
    echo $result['message'] . "\n";
    $error += $result['error'];
  }
  echo "Tests ran with {$error}/{$num} errors.\n";
  
  echo "\n\nTesting 60 series (number) errors\n\n";
  $error = 0;
  $tests = array(
    //Basic integer or float tests
    array(array('21', 'num', $formats['num'][0]),0),
    array(array('21.2', 'num', $formats['num'][0]),61),
    array(array('foo', 'num', $formats['num'][0]),61),
    array(array('21', 'num', $formats['num'][1]),0),
    array(array('21.2', 'num', $formats['num'][1]),0),
    array(array('foo', 'num', $formats['num'][1]),62),
    
    //Testing resolution
    array(array('0', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
    array(array('3', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
    array(array('2.5', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
    array(array('2.51', 'num', $formats['num'][1], true, null, false, array(0,3,0.5), array(false,false,true)),67),
    array(array('2.51', 'num', $formats['num'][1], true, null, false, array(0,3,0.5)),0),
    array(array('3.2', 'num', $formats['num'][1], true, null, false, array(0,3.2,0.5)),0),
    array(array('0', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
    array(array('5', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
    array(array('2.5', 'num', $formats['num'][0], true, null, false, array(0,50,5)),61),
    array(array('25', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
    array(array('24', 'num', $formats['num'][0], true, null, false, array(0,50,5), array(false,false,true)),67),
    array(array('24', 'num', $formats['num'][0], true, null, false, array(0,50,5)),0),
    
    //Testing ranges (integers)
    array(array('-1', 'num', $formats['num'][0], true, null, false, array(0,3,null)),64),
    array(array('0', 'num', $formats['num'][0], true, null, false, array(0,3,null)),0),
    array(array('3', 'num', $formats['num'][0], true, null, false, array(0,3,null)),0),
    array(array('4', 'num', $formats['num'][0], true, null, false, array(0,3,null)),66),
    array(array('-1', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(true, true, false)),63),
    array(array('0', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(true, true, false)),63),
    array(array('1', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(true, true, false)),0),
    array(array('2', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(true, true, false)),0),
    array(array('3', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(true, true, false)),65),
    array(array('4', 'num', $formats['num'][0], true, null, false, array(0,3,null), array(true, true, false)),65),
    
    //Testing ranges (floats)
    array(array('-1', 'num', $formats['num'][1], true, null, false, array(0,3,null)),64),
    array(array('0', 'num', $formats['num'][1], true, null, false, array(0,3,null)),0),
    array(array('3', 'num', $formats['num'][1], true, null, false, array(0,3,null)),0),
    array(array('4', 'num', $formats['num'][1], true, null, false, array(0,3,null)),66),
    array(array('-1', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(true, true, false)),63),
    array(array('0', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(true, true, false)),63),
    array(array('0.00001', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(true, true, false)),0),
    array(array('2.99999', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(true, true, false)),0),
    array(array('3', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(true, true, false)),65),
    array(array('4', 'num', $formats['num'][1], true, null, false, array(0,3,null), array(true, true, false)),65),
    

  );
  $num = count($tests);
  foreach ($tests as $test){
    $result = call_user_func_array('lwt_test_validation',$test);
    echo $result['message'] . "\n";
    $error += $result['error'];
  }
  echo "Tests ran with {$error}/{$num} errors.\n";
  
  

  
}
