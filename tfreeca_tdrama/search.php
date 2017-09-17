<?php
/********************************************************************************\
| MIT License                                                                    |
|                                                                                |
| Copyright (c) 2017 JunNineKim                                                  |
|--------------------------------------------------------------------------------|
| Permission is hereby granted, free of charge, to any person obtaining a copy   |
| of this software and associated documentation files (the "Software"), to deal  |
| in the Software without restriction, including without limitation the rights   |
| to use, copy, modify, merge, publish, distribute, sublicense, and/or sell      |
| copies of the Software, and to permit persons to whom the Software is          |
| furnished to do so, subject to the following conditions:                       |
|                                                                                |
| The above copyright notice and this permission notice shall be included in all |
| copies or substantial portions of the Software.                                |
|                                                                                |
| THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR     |
| IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,       |
| FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE    |
| AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER         |
| LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,  |
| OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE  |
| SOFTWARE.                                                                      |
\********************************************************************************/
?><?php
class SynoDLMSearchTfreecaDrama {
  private $categories = array("tmovie","tdrama","tent","tv","tani","tmusic","util");
  private $qurl = "http://www.tfreeca22.com/board.php?b_id=tdrama&mode=list&sc=%s&x=0&y=0&page=1";
  private $purl = "http://www.tfreeca22.com/torrent_info.php?bo_table=tdrama&wr_id=%d";
  private $debug = false;//false true

  private function DebugLog($msg) {
    if ($this->debug==true) {
      $dt = date('Y/m/d H:i:s');
      $bt = debug_backtrace();
      $func = isset($bt[1]) ? $bt[1]['function'] : '__main__';
      $caller = array_shift($bt);
      $file = basename($caller['file']);
      $output = sprintf("[%s] <%s:%s:%d> %s\n", $dt, $file, $func, $caller['line'], print_r($msg, true));
          file_put_contents('/tmp/torrentTfreecaTdrama.log',$output."\r\n\r\n",FILE_APPEND);
    }
  }

  public function __construct() {
  }

  public function prepare($curl, $query) {
    $url = sprintf($this->qurl, urlencode($query));
    $this->DebugLog($query);
    $this->DebugLog($curl);
    //$curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    $response = curl_exec($curl);
    $response = str_replace("&","&amp;",$response);
    //$this->DebugLog($response);
    return $response;
  }
  
  private function getInfo($id) {
    $curl = curl_init();
    $this->DebugLog("id :".$id);
    $url = sprintf($this->purl, $id);
    $this->DebugLog($url);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    $response = curl_exec($curl);
    $response = str_replace("&","&amp;",$response);
    curl_close($curl);
    //$this->DebugLog($response);
    $regexp2 = "<script type=(.*<br><\/div>)";
    $regexp_title = "class=\"torrent_list\">(.*)\(";
    $regexp_hash = "";
    $regexp_download = "<a href=\"(magnet:\?xt=urn:btih:.*)\"";
    $regexp_size = "\.(mkv|MKV|avi|AVI|mp4|MP4|exe|EXE|zip|ZIP|rar|RAR) \(([0-9\.]+)(GB|MB)\)";
    $regexp_date = "<span class=\"mw_basic_view_datetime media-date\"><span title=.*>(.*)<\/span><\/span>";
    $res=0;
    preg_match_all("/$regexp2/siU", $response, $matches2, PREG_SET_ORDER);
    foreach($matches2 as $match2) :
      $res=$res+1;
      if($res==1){
        $this->DebugLog($match2[0]);
      }
      if(preg_match_all("/$regexp_title/siU", $match2[0], $matches, PREG_SET_ORDER)) {
              foreach($matches as $match) {
                      $info['title'] = $match[1];
                      $this->DebugLog("Title :".$match[1]);
                      $info['hash'] = md5($match[1]);
                      $this->DebugLog("hash :".md5($match[1]));
              }
      }
      if(preg_match_all("/$regexp_download/siU", $match2[0], $matches, PREG_SET_ORDER)) {
              foreach($matches as $match) {
                      $info['download'] = $match[1];
                      $this->DebugLog("download :".$match[1]);
              }
      }
      if(preg_match_all("/$regexp_size/siU", $match2[0], $matches, PREG_SET_ORDER)) {
              foreach($matches as $match) {
                     $size = str_replace(",",".",$match[2]);
                     switch (trim($match[3])){
                             case 'KB':
                                     $size = $size * 1024;
                                     break;
                             case 'MB':
                                     $size = $size * 1024 * 1024;
                                     break;
                             case 'GB':
                                     $size = $size * 1024 * 1024 * 1024;
                                     break;
                             case 'TB':
                                     $size = $size * 1024 * 1024 * 1024 * 1024;
                                     break;
                     }
                     $size = floor($size);
                     $info['size'] = $size;
                     $this->DebugLog("size :".$size);
              }
      }
      if(preg_match_all("/$regexp_date/siU", $match2[0], $matches, PREG_SET_ORDER)) {
              foreach($matches as $match) {
                      $info['date'] = $match[1];
                      $this->DebugLog("date :".$match[1]);
              }
      }
    endforeach;
    return $info;
  }
  
  public function parse($plugin, $response) {
    $this->DebugLog($plugin);
    $response = preg_replace("/class=\"stitle(\d+)\">/","class=\"stitle\"",$response);
    $response = str_replace("</span>","",str_replace("<span class='sc_font'>","",$response));
    $this->DebugLog("After response2 :".$response);
    $regexp = "<a href=\"board.php\?.*><span class=\"ca\">(.*)<\/a> <a href=\"board\.php\?mode=view&b_id=tdrama&id=(.*)&sc=.*class=\"stitle\" (.*)<\/a>.*<td class=\"datetime\">([0-9\,\-]+)<\/td>";
    $this->DebugLog("Parse regexp :".$regexp);
    $res=0;
    if(preg_match_all("/$regexp/siU", $response, $matches, PREG_SET_ORDER)) {
      $title="Unknown title";
      $download="Unknown download";
      $size=0;
      $datetime="1900-12-31";
      $current_time= date('Y-m-d H:i:s');
      $page="Default page";
      $hash="Hash unknown";
      $seeds=0;
      $leechs=0;
      $category="Unknown category";
      foreach($matches as $match) {
          $status    = $match[1];
          $id        = $match[2];
          $titleHd   = $status.str_replace("</span>","",$match[3]);
          $date      = $match[4];// 06-28
          if(substr($current_time,5,2) >=substr($date,0,2)) {
          	$guess_year  = substr($current_time,0,4);
          } else {
            $guess_year  = substr($current_time,0,4)-1;
          }
          $date    = $guess_year."-".$date." 00:00:00";
          $info      = $this->getInfo($id);
          $title     = $titleHd;
          $download  = $info['download'];
          $size      = (isset($info['size']) && !is_null($info['size'])) ? $info['size'] : 0;
          $datetime  = $date;
          $hash      = $info['hash'];
          $category  = "Drama";
          $seeds     = 0;
          $leechs    = 0;
          $page      = sprintf($this->purl, $id);
          $this->DebugLog("Parse status :".$status);
          $this->DebugLog("Parse title1 :".$titleHd);
          $this->DebugLog("Parse id :".$id);
          $this->DebugLog("Parse date :".$date);
          $this->DebugLog("Parse title :".$title);
          $this->DebugLog("Parse download :".$download);
          $this->DebugLog("Parse size :".$size);
          $this->DebugLog("Parse datetime :".$datetime);
          $this->DebugLog("Parse page :".$page);
          $this->DebugLog("Parse hash :".$hash);
          $this->DebugLog("Parse seeds :".$seeds);
          $this->DebugLog("Parse leechs :".$leechs);
          $this->DebugLog("Parse category :".$category);
          if (!( $title == '' || $download == '' )) {
            $this->DebugLog("If so Parse id :".$id);
            $plugin->addResult($title, $download, $size, $datetime, $page, $hash, $seeds, $leechs, $category);
            $res++; 
              if($res >=28) break; //28일때 화면 30 line display
          } else {
            $this->DebugLog("Else Parse id :".$id);
          }
      }
    }
    return $res;  
  }
}
?>