<?php
/**
 * Aplikasi untuk konsep one file
 * Agus Sudarmanto, S.Kom.
 * 2014 Jun 27
 * rev Thursday 30th of October 2014 02:10:36 AM */
session_start();
require_once('base.php');

class apps extends base {
    var $v          = "";
    var $err        = "";
    var $info       = "";
    var $basepath   = BASEPATH;
    var $module     = "";
    var $URI        = "";
    var $blankpage  = false;
    var $_SHOW_DB_QUERY = false;
    var $_SHOW_DB_ERROR = false;
    var $_DB_NAME = DB_NAME;

    /**
     *
     * Constructor
     *
     **/
    function apps() {
        $this->URI = explode("/", $_SERVER["REQUEST_URI"]);
        $class_methods = get_class_methods($this);
        $this->db_connect();
        $this->save_params();

        if (!empty($_SESSION['user']['id'])) {
            if (!empty($this->URI[ACTION_PARAM_IDX]) && $this->URI[ACTION_PARAM_IDX]=='page') 
            {
                $callfunc = "action_" . $this->URI[APPS_PARAM_IDX] . "_" . $this->URI[MODULE_PARAM_IDX];
            }
            else 
            {
                $callfunc = "action_" . $this->URI[APPS_PARAM_IDX] . "_" . $this->URI[MODULE_PARAM_IDX] . (!empty($this->URI[ACTION_PARAM_IDX]) ? $this->URI[ACTION_PARAM_IDX] : "");
            }

            $this->module = $this->URI[MODULE_PARAM_IDX];

            if (in_array($callfunc, $class_methods)) {
                $this->prepare();
                  call_user_func("apps::" . $callfunc, (isset($this->URI[ACTION_PARAM_IDX+1]) ? mysql_real_escape_string($this->URI[ACTION_PARAM_IDX+1]) : null));
                $this->show();
                $this->db_disconnect();
            } else {
                $this->v .= "<div>sorry no methods</div>";
                $this->show();
            }
        } else {
            $this->db_connect();
            call_user_func("apps::action_login_", null);
            $this->show();
            $this->db_disconnect();
        }
    }

    /**
     *
     * Prepare
     *
     **/
    function prepare() 
    {
      if ( isset($this->post['session']['act']) && $_SESSION['act'] != $this->post['session']['act']) $_SESSION['act'] = $this->post['session']['act'];
      if ( isset($this->post['session']['xid']) && $_SESSION['xid'] != $this->post['session']['xid']) $_SESSION['xid'] = $this->post['session']['xid'];

      if (!empty($_SESSION['xid'])) $this->session['xid'] = $_SESSION['xid'];
      if (!empty($_SESSION['act'])) $this->session['act'] = $_SESSION['act'];
      if (!empty($_SESSION['last_sql'])) $this->session['last_sql'] = $_SESSION['last_sql'];
    }

    /**
     *
     * Escaping string
     *
     **/
    function escape_query($string) 
    {
        if (is_array($string)) {
            while (list($key,$val) = each($string)) {
                $string[$key] = $this->escape_query($val);
            }
        } else {
            $string = mysql_real_escape_string($string);
            $string = implode("",explode("\\",$string));
            $string = stripslashes(trim($string));
            $string = str_replace(array('00-00-0000'), array(''), $string);
            return strtr($string, array(
                "\0" => "",
                "'"  => "&#39;",
                "\"" => "&#34;",
                "\\" => "&#92;",
                "<"  => "<",
                ">"  => ">",
            ));
        }
    }

    /**
     *
     * Securing all parameters
     *
     **/
    function save_params()
    {
        if (isset($_GET)) 
        {
            while (list($key, $val) = each($_GET))
            {
                if (is_array($val)) {
                    while (list($k) = each($val)) {
                        $this->get[$key][$k] = $this->escape_query($val[$k]);
                    }
                } else {
                    $this->get[$key] = $this->escape_query($val);
                }
            }
        }

        if (isset($_POST)) 
        {
            while (list($key, $val) = each($_POST))
            {
                if (is_array($val)) {
                    while (list($k) = each($val)) {
                        $this->post[$key][$k] = $this->escape_query($val[$k]);
                    }
                } else {
                    $this->post[$key] = $this->escape_query($val);
                }
            }
        }
    }

    function action_setting_about()
    {
        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div> About</div></a>
        </div>
        
        <div class='panel panel-default'>
            <div class='panel-heading'><span class='glyphicon glyphicon-phone-alt'></span> About</div>
            <div class='panel-body'>
                <div class='col-sm-2'>Application name</div><div class='col-sm-10'>Jadwal pimpinan v.0.2.2<br>Dana Operasional v.0.2.1<br><br></div>
                <div class='col-sm-2'>Created by</div><div class='col-sm-10'>Agus Sudarmanto, S.Kom.<br><br></div>
                <div class='col-sm-2'>Contact</div><div class='col-sm-10'>
                    Phone : <b>0821-23-8585-43</b> (Simpati) - 
                    <b>0856-9546-7676</b> (IM3 whatsapp ready)<br>
                    BBM : <b>742AD1BB</b><br>
                    eMail : agus.sudarmanto (at) gmail.com<br>
                    FB : <a href='http://www.facebook.com/agussudarmanto/'>http://www.facebook.com/agussudarmanto/</a><br>
                    TW : <a href='http://www.twitter.com/agussudarmanto/'>http://www.twitter.com/agussudarmanto/</a><br>
                    Github : <a href='http://www.github.com/agus.sudarmanto/'>http://www.github.com/agus.sudarmanto/</a><br>
                </div>
            </div>
        </div>";
    }

    /**
     *
     * Write pre variables
     *
     **/
    function pre($var)
    {
        $this->v .= "<pre>".print_r($var, true)."</pre>";
    }

    /**
     *
     * Show all pages
     *
     **/
    function show()
    {
        if (!$this->blankpage) $this->header();
        $this->show_flash();
        echo $this->v;
        $this->show_error();
        if (!$this->blankpage) $this->footer();
    }

    /**
     *
     * Write information message from cache if exist
     *
     **/
    function show_flash()
    {
        if (!empty($_SESSION['flash'])) {
            echo "<br><div class='alert alert-warning alert-dismissable'>
            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
            ".$_SESSION['flash']."
            </div>";
            $_SESSION['flash'] = "";
        }
    }

    /**
     *
     * Write error message from cache if exist
     *
     **/
    function show_error()
    {
        if (!empty($this->err)) echo "<div class='alert alert-danger alert-dismissable'>
            <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
            ".$this->err."
            </div>";
    }

    /**
     *
     * Send information message to cache
     *
     **/
    function flash($str)
    {
        if (!isset($_SESSION['flash'])) $_SESSION['flash'] = "";
        $_SESSION['flash'] .= (!empty($this->info) ? "<br>" : "") . $str;
    }

    /**
     *
     * Send error message to cache
     *
     **/
    function error($str)
    {
        $this->err .= (!empty($this->err) ? "<br>" : "") . $str;
    }

    /**
     *
     * Redirecting page
     *
     **/
    function go_to($location)
    {
        $this->v .= "<script>setTimeout(function(){location.href='".$location."';}, 3000);</script>";
        $_SESSION['goto'] = true;
    }

    /**
     *
     * Do database connection
     *
     **/
    function db_connect()
    {
        $this->dbconn = mysql_connect(DB_SERVER, DB_USER, DB_PASSWORD);
        if (!$this->dbconn) {
            $this->pre("Could not connect: " . mysql_error());
        } else {
            $db_selected = mysql_select_db($this->_DB_NAME, $this->dbconn);
            if (!$db_selected) {
                $this->pre(mysql_error());
            }
        }
    }

    /**
     *
     * Closing database connection
     *
     **/
    function db_disconnect()
    {
        mysql_close($this->dbconn);
    }

    /**
     *
     * Do query to database
     *
     **/
    function db_query($sql, $arr_filter=null)
    {
        $filter = "";
        if ($arr_filter != null) {
            foreach ($arr_filter as $val) {
                if (!empty($this->post['filter'][$val]) || $this->post['filter'][$val]=='0') {
                    if (substr($val,0,3)=='tgl') {
                    	$filter .= (empty($filter)?"":" AND ")."$val = STR_TO_DATE('".$this->post['filter'][$val]."', '%d-%m-%Y')";
                    } else {
                    	$filter .= (empty($filter)?"":" AND ")."$val LIKE '%".$this->post['filter'][$val]."%'";
                    }
                }
            }
        }

        if (!empty($filter)) {
            if (strpos($sql, 'WHERE') !== false) {
              $filter = " AND ".$filter;
            } else {
              $filter = " WHERE ".$filter;
            }
        }

        $sql_len = strlen($sql);
        $search  = array(' GROUP BY', ' ORDER', ' LIMIT');
        
        while (list($key) = each($search)) {
            $sql = str_replace($search[$key], $filter . $search[$key], $sql);
            if ($sql_len!=strlen($sql)) break;
        }

        if ($sql_len==strlen($sql)) $sql .= $filter;

        if ($this->_SHOW_DB_QUERY) $this->pre($sql);
        if ($this->save_query) { 
            $_SESSION['last_sql'] = substr($sql, 0, strpos($sql, 'LIMIT'));
            $this->save_query = false; 
        }
        $this->db_result = mysql_query($sql);
        if ( mysql_error() != FALSE && $this->_SHOW_DB_ERROR ) $this->error($sql . '<br><br>' . mysql_error());

        if ( mysql_insert_id() != null) $this->db_last_insert_id = mysql_insert_id();

        return $this->db_result;
    }

    /**
     *
     * Get data from query
     *
     **/
    function db_fetch()
    {
        $ret = null;
        if ($this->db_numrows()>0) {
            $ret = mysql_fetch_array($this->db_result);
            
            if (is_array($ret)) {
                $this->db_filter_row($ret);
            }
        }
        return $ret;
    }

    /**
     *
     * Filtering row(s) from query
     *
     **/
    function db_filter_row(&$row)
    {
        while (list($k,$v) = each($row))
        {
            $row[$k] = $this->escape_query($v);
        }
    }

    /**
     *
     * Get number of affected row(s) from query
     *
     **/
    function db_affected()
    {
        $ret = mysql_affected_rows($this->dbconn);
        return $ret;
    }

    /**
     *
     * Get number of row(s) from query
     *
     **/
    function db_numrows()
    {
        $ret = mysql_num_rows($this->db_result);
        return $ret;
    }

    function pagination($total, $per_page=10, $page=1, $url='?') {  
        $adjacents = "2";
          
        $prevlabel = "&lsaquo;";
        $nextlabel = "&rsaquo;";
        $lastlabel = "&rsaquo;&rsaquo;";
          
        $page = ($page == 0 ? 1 : $page); 
        $start = ($page - 1) * $per_page;                              
          
        $prev = $page - 1;                         
        $next = $page + 1;
          
        $lastpage = ceil($total/$per_page);
          
        $lpm1 = $lastpage - 1;
          
        $pagination = "";
        if($lastpage > 1){  
            $pagination .= "<ul class='pagination pagination-sm'>";
                  
                if ($page > 1) $pagination.= "<li><a href='{$url}/page/{$prev}/' onclick='filter(this); return false;'>{$prevlabel}</a></li>";
                  
            if ($lastpage < 7 + ($adjacents * 2)){  
                for ($counter = 1; $counter <= $lastpage; $counter++){
                    if ($counter == $page)
                        $pagination.= "<li><a class='active'>{$counter}</a></li>";
                    else
                        $pagination.= "<li><a href='{$url}/page/{$counter}/' onclick='filter(this); return false;'>{$counter}</a></li>";                   
                }
              
            } elseif($lastpage > 5 + ($adjacents * 2)){
                  
                if($page < 1 + ($adjacents * 2)) {
                      
                    for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++){
                        if ($counter == $page)
                            $pagination.= "<li><a class='active'>{$counter}</a></li>";
                        else
                            $pagination.= "<li><a href='{$url}/page/{$counter}/' onclick='filter(this); return false;'>{$counter}</a></li>";                   
                    }
                    $pagination.= "<li class='dot'><a href='#'>...</a></li>";
                    $pagination.= "<li><a href='{$url}/page/{$lpm1}/' onclick='filter(this); return false;'>{$lpm1}</a></li>";
                    $pagination.= "<li><a href='{$url}/page/{$lastpage}/' onclick='filter(this); return false;'>{$lastpage}</a></li>";
                          
                } elseif($lastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2)) {
                      
                    $pagination.= "<li><a href='{$url}/page/1/' onclick='filter(this); return false;'>1</a></li>";
                    $pagination.= "<li><a href='{$url}/page/2/' onclick='filter(this); return false;'>2</a></li>";
                    $pagination.= "<li class='dot'><a href='#'>...</a></li>";
                    for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++) {
                        if ($counter == $page)
                            $pagination.= "<li><a class='active'>{$counter}</a></li>";
                        else
                            $pagination.= "<li><a href='{$url}/page/{$counter}/' onclick='filter(this); return false;'>{$counter}</a></li>";                   
                    }
                    $pagination.= "<li class='dot'><a href='#'>...</a></li>";
                    $pagination.= "<li><a href='{$url}/page/{$lpm1}/' onclick='filter(this); return false;'>{$lpm1}</a></li>";
                    $pagination.= "<li><a href='{$url}/page/{$lastpage}/' onclick='filter(this); return false;'>{$lastpage}</a></li>";     
                      
                } else {
                      
                    $pagination.= "<li><a href='{$url}/page/1/' onclick='filter(this); return false;'>1</a></li>";
                    $pagination.= "<li><a href='{$url}/page/2/' onclick='filter(this); return false;'>2</a></li>";
                    $pagination.= "<li class='dot'><a href='#'>...</a></li>";
                    for ($counter = $lastpage - (2 + ($adjacents * 2)); $counter <= $lastpage; $counter++) {
                        if ($counter == $page)
                            $pagination.= "<li><a class='active'>{$counter}</a></li>";
                        else
                            $pagination.= "<li><a href='{$url}/page/{$counter}/' onclick='filter(this); return false;'>{$counter}</a></li>";                   
                    }
                }
            }
              
                if ($page < $counter - 1) {
                    $pagination.= "<li><a href='{$url}/page/{$next}/' onclick='filter(this); return false;'>{$nextlabel}</a></li>";
                    $pagination.= "<li><a href='{$url}/page/{$lastpage}/' onclick='filter(this); return false;'>{$lastlabel}</a></li>";
                }
              
            $pagination.= "</ul>";       
        }
          
        return $pagination;
    }

    /**
     *
     * List user_ms_group
     *
     **/
    function action_user_ms_group()
    {

        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Pengguna</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Group</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Group</div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."user/ms_group/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."user/ms_group/add/'\" title='Input Group baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Nama kelompok pengguna</span>
                                        <input type='text' class='form-control' name='filter[name]' value='".(isset($this->post['filter']['name'])?$this->post['filter']['name']:null)."' placeholder='Input Nama kelompok pengguna'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Sebagai administrator ?</span>
                                        <input type='text' class='form-control' name='filter[admin]' value='".(isset($this->post['filter']['admin'])?$this->post['filter']['admin']:null)."' placeholder='Input Sebagai administrator ?'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama kelompok pengguna</th>
                                <th>Sebagai administrator ?</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM user_ms_group WHERE 1=1 {$this->groupfield}", array('name','admin'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT *  FROM user_ms_group WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('name','admin'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[name]."</td>
                                <td>".$row[admin]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."user/ms_group/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."user/ms_group/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."user/ms_group");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of user_ms_group
     *
     **/
    function action_user_ms_groupview($id) {
 
        $res = $this->db_query("SELECT *  FROM user_ms_group WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Pengguna</div></a>
                <a href='".$this->basepath."user/ms_group/' class='btn btn-default'><div>Daftar Group</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Group</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Group
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."user/ms_group/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Nama kelompok pengguna</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$row['name']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Sebagai administrator ?</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$row['admin']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."user/ms_group/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."user/ms_group/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."user/ms_group/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add user_ms_group
     *
     **/
    function action_user_ms_groupadd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO user_ms_group (name,admin,created,ipaddress) 
                VALUES ('".$this->post['name']."','".$this->post['admin']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."user/ms_group");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
     
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Pengguna</div></a>
                <a href='".$this->basepath."user/ms_group/' class='btn btn-default'><div>Daftar Group</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Group</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Group</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Nama kelompok pengguna</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Nama kelompok pengguna' value='".$row['name']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Sebagai administrator ?</label>
                            <div class='col-sm-7'>
                                <input name='admin' placeholder='Sebagai administrator ?' value='".$row['admin']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."user/ms_group/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit user_ms_group
     *
     **/
    function action_user_ms_groupedit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE user_ms_group SET name = '".$this->post['name']."', admin = '".$this->post['admin']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."user/ms_group");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
     
            $res = $this->db_query("SELECT *  FROM user_ms_group WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Pengguna</div></a>
                    <a href='".$this->basepath."user/ms_group/' class='btn btn-default'><div>Daftar Group</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Group</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Group</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Nama kelompok pengguna</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Nama kelompok pengguna' value='".$row['name']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Sebagai administrator ?</label>
                            <div class='col-sm-7'>
                                <input name='admin' placeholder='Sebagai administrator ?' value='".$row['admin']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."user/ms_group/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete user_ms_group
     *
     **/
    function action_user_ms_groupdelete($id) {
        $res = $this->db_query("DELETE FROM user_ms_group WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."user/ms_group");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export user_ms_group to HTML
     *
     **/
    function action_user_ms_grouphtml()
    {
        $this->blankpage = true;

        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Group</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Nama kelompok pengguna</th>
                                <th>Sebagai administrator ?</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$row[name]."</td>
                                <td>".$row[admin]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export user_ms_group view to HTML
     *
     **/
    function action_user_ms_groupviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT *  FROM user_ms_group WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Group</h1>
            <table>
                <tr><td>Nama kelompok pengguna</td><td>".$row['name']."</td></tr>
                <tr><td>Sebagai administrator ?</td><td>".$row['admin']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List user_user
     *
     **/
    function action_user_user()
    {

        $this->db_query("SELECT * FROM user_ms_group");
        while ( $row = $this->db_fetch() ) { $arr_ms_group[$row['id']] = $row['name']; }
        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Pengguna</div></a>
            <a href='#' class='btn btn-default'><div>Daftar User</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar User                  </div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."user/user/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."user/user/add/'\" title='Input User baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Alamat eMail</span>
                                        <input type='text' class='form-control' name='filter[email]' value='".(isset($this->post['filter']['email'])?$this->post['filter']['email']:null)."' placeholder='Input Alamat eMail'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Username</span>
                                        <input type='text' class='form-control' name='filter[name]' value='".(isset($this->post['filter']['name'])?$this->post['filter']['name']:null)."' placeholder='Input Username'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Password</span>
                                        <input type='text' class='form-control' name='filter[password]' value='".(isset($this->post['filter']['password'])?$this->post['filter']['password']:null)."' placeholder='Input Password'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Nama lengkap pengguna</span>
                                        <input type='text' class='form-control' name='filter[namalengkap]' value='".(isset($this->post['filter']['namalengkap'])?$this->post['filter']['namalengkap']:null)."' placeholder='Input Nama lengkap pengguna'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>NIP pengguna</span>
                                        <input type='text' class='form-control' name='filter[nip]' value='".(isset($this->post['filter']['nip'])?$this->post['filter']['nip']:null)."' placeholder='Input NIP pengguna'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Jabatan pengguna</span>
                                        <input type='text' class='form-control' name='filter[jabatan]' value='".(isset($this->post['filter']['jabatan'])?$this->post['filter']['jabatan']:null)."' placeholder='Input Jabatan pengguna'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Kelompok pengguna</span>
                                        <select class='form-control' name='filter[ms_group_id]'>
                                            <option value=''>-- Pilih Kelompok pengguna--</option>";
            while (list($key,$val) = each($arr_ms_group)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_group_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Login terakhir</span>
                                        <input type='text' class='form-control' name='filter[lastlogin]' value='".(isset($this->post['filter']['lastlogin'])?$this->post['filter']['lastlogin']:null)."' placeholder='Input Login terakhir'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>IP Login terakhir</span>
                                        <input type='text' class='form-control' name='filter[lastloginip]' value='".(isset($this->post['filter']['lastloginip'])?$this->post['filter']['lastloginip']:null)."' placeholder='Input IP Login terakhir'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Alamat eMail</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Nama lengkap pengguna</th>
                                <th>NIP pengguna</th>
                                <th>Jabatan pengguna</th>
                                <th>Kelompok pengguna</th>
                                <th>Login terakhir</th>
                                <th>IP Login terakhir</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM user_user WHERE 1=1 {$this->groupfield}", array('email','name','password','namalengkap','nip','jabatan','ms_group_id','lastlogin','lastloginip'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT *  FROM user_user WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('email','name','password','namalengkap','nip','jabatan','ms_group_id','lastlogin','lastloginip'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[email]."</td>
                                <td>".$row[name]."</td>
                                <td>".$row[password]."</td>
                                <td>".$row[namalengkap]."</td>
                                <td>".$row[nip]."</td>
                                <td>".$row[jabatan]."</td>
                                <td>".$arr_ms_group[$row[ms_group_id]]."</td>
                                <td>".$row[lastlogin]."</td>
                                <td>".$row[lastloginip]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."user/user/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."user/user/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."user/user");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of user_user
     *
     **/
    function action_user_userview($id) {
        $this->db_query("SELECT * FROM user_ms_group");
        while ( $row = $this->db_fetch() ) { $arr_ms_group[$row['id']] = $row['name']; }
 
        $res = $this->db_query("SELECT *  FROM user_user WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Pengguna</div></a>
                <a href='".$this->basepath."user/user/' class='btn btn-default'><div>Daftar User</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data User</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data User
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."user/user/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Alamat eMail</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$row['email']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Username</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$row['name']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Password</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input3' class='form-control' readonly value='".$row['password']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Nama lengkap pengguna</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input4' class='form-control' readonly value='".$row['namalengkap']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>NIP pengguna</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input5' class='form-control' readonly value='".$row['nip']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Jabatan pengguna</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input6' class='form-control' readonly value='".$row['jabatan']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input7' class='col-sm-5 control-label'>Kelompok pengguna</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input7' class='form-control' readonly value='".$arr_ms_group[$row['ms_group_id']]."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input8' class='col-sm-5 control-label'>Login terakhir</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input8' class='form-control' readonly value='".$row['lastlogin']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input9' class='col-sm-5 control-label'>IP Login terakhir</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input9' class='form-control' readonly value='".$row['lastloginip']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."user/user/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."user/user/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."user/user/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add user_user
     *
     **/
    function action_user_useradd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO user_user (email,name,password,namalengkap,nip,jabatan,ms_group_id,lastlogin,lastloginip,created,ipaddress) 
                VALUES ('".$this->post['email']."','".$this->post['name']."','".$this->post['password']."','".$this->post['namalengkap']."','".$this->post['nip']."','".$this->post['jabatan']."','".$this->post['ms_group_id']."','".$this->post['lastlogin']."','".$this->post['lastloginip']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."user/user");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
            $this->db_query("SELECT * FROM user_ms_group");
        while ( $row = $this->db_fetch() ) { $arr_ms_group[$row['id']] = $row['name']; }
 
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Pengguna</div></a>
                <a href='".$this->basepath."user/user/' class='btn btn-default'><div>Daftar User</div></a>
                <a href='#' class='btn btn-default'><div>Tambah User</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data User</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Alamat eMail</label>
                            <div class='col-sm-7'>
                                <input name='email' placeholder='Alamat eMail' value='".$row['email']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Username</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Username' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Password</label>
                            <div class='col-sm-7'>
                                <input name='password' placeholder='Password' value='".$row['password']."' title='' id='input3' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Nama lengkap pengguna</label>
                            <div class='col-sm-7'>
                                <input name='namalengkap' placeholder='Nama lengkap pengguna' value='".$row['namalengkap']."' title='' id='input4' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>NIP pengguna</label>
                            <div class='col-sm-7'>
                                <input name='nip' placeholder='NIP pengguna' value='".$row['nip']."' title='' id='input5' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Jabatan pengguna</label>
                            <div class='col-sm-7'>
                                <input name='jabatan' placeholder='Jabatan pengguna' value='".$row['jabatan']."' title='' id='input6' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input7' class='col-sm-5 control-label'>Kelompok pengguna</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_group_id' id='input7'>
                                    <option value=''>-- Pilih kelompok pengguna--</option>";
                while (list($key,$val) = each($arr_ms_group)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_group_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input8' class='col-sm-5 control-label'>Login terakhir</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_datetime' data-date='".$row['lastlogin']."' data-date-format='dd-mm-yyyy hh:ii' data-link-field='input8' data-link-format='dd-mm-yyyy hh:ii'>
                                    <input size='16' type='text' value='".$row['lastlogin']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='lastlogin' placeholder='Login terakhir' value='".$row['lastlogin']."' title='' id='input8' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input9' class='col-sm-5 control-label'>IP Login terakhir</label>
                            <div class='col-sm-7'>
                                <input name='lastloginip' placeholder='IP Login terakhir' value='".$row['lastloginip']."' title='' id='input9' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."user/user/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit user_user
     *
     **/
    function action_user_useredit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE user_user SET email = '".$this->post['email']."', name = '".$this->post['name']."', password = '".$this->post['password']."', namalengkap = '".$this->post['namalengkap']."', nip = '".$this->post['nip']."', jabatan = '".$this->post['jabatan']."', ms_group_id = '".$this->post['ms_group_id']."', lastlogin = '".$this->post['lastlogin']."', lastloginip = '".$this->post['lastloginip']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."user/user");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
            $this->db_query("SELECT * FROM user_ms_group");
        while ( $row = $this->db_fetch() ) { $arr_ms_group[$row['id']] = $row['name']; }
 
            $res = $this->db_query("SELECT *  FROM user_user WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Pengguna</div></a>
                    <a href='".$this->basepath."user/user/' class='btn btn-default'><div>Daftar User</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data User</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data User</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Alamat eMail</label>
                            <div class='col-sm-7'>
                                <input name='email' placeholder='Alamat eMail' value='".$row['email']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Username</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Username' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Password</label>
                            <div class='col-sm-7'>
                                <input name='password' placeholder='Password' value='".$row['password']."' title='' id='input3' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Nama lengkap pengguna</label>
                            <div class='col-sm-7'>
                                <input name='namalengkap' placeholder='Nama lengkap pengguna' value='".$row['namalengkap']."' title='' id='input4' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>NIP pengguna</label>
                            <div class='col-sm-7'>
                                <input name='nip' placeholder='NIP pengguna' value='".$row['nip']."' title='' id='input5' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Jabatan pengguna</label>
                            <div class='col-sm-7'>
                                <input name='jabatan' placeholder='Jabatan pengguna' value='".$row['jabatan']."' title='' id='input6' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input7' class='col-sm-5 control-label'>Kelompok pengguna</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_group_id' id='input7'>
                                    <option value=''>-- Pilih kelompok pengguna--</option>";
                while (list($key,$val) = each($arr_ms_group)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_group_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input8' class='col-sm-5 control-label'>Login terakhir</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_datetime' data-date='".$row['lastlogin']."' data-date-format='dd-mm-yyyy hh:ii' data-link-field='input8' data-link-format='dd-mm-yyyy hh:ii'>
                                    <input size='16' type='text' value='".$row['lastlogin']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='lastlogin' placeholder='Login terakhir' value='".$row['lastlogin']."' title='' id='input8' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input9' class='col-sm-5 control-label'>IP Login terakhir</label>
                            <div class='col-sm-7'>
                                <input name='lastloginip' placeholder='IP Login terakhir' value='".$row['lastloginip']."' title='' id='input9' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."user/user/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete user_user
     *
     **/
    function action_user_userdelete($id) {
        $res = $this->db_query("DELETE FROM user_user WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."user/user");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export user_user to HTML
     *
     **/
    function action_user_userhtml()
    {
        $this->blankpage = true;

        $this->db_query("SELECT * FROM user_ms_group");
        while ( $row = $this->db_fetch() ) { $arr_ms_group[$row['id']] = $row['name']; }
        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar User</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Alamat eMail</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Nama lengkap pengguna</th>
                                <th>NIP pengguna</th>
                                <th>Jabatan pengguna</th>
                                <th>Kelompok pengguna</th>
                                <th>Login terakhir</th>
                                <th>IP Login terakhir</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$row[email]."</td>
                                <td>".$row[name]."</td>
                                <td>".$row[password]."</td>
                                <td>".$row[namalengkap]."</td>
                                <td>".$row[nip]."</td>
                                <td>".$row[jabatan]."</td>
                                <td>".$arr_ms_group[$row[ms_group_id]]."</td>
                                <td>".$row[lastlogin]."</td>
                                <td>".$row[lastloginip]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export user_user view to HTML
     *
     **/
    function action_user_userviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT *  FROM user_user WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data User</h1>
            <table>
                <tr><td>Alamat eMail</td><td>".$row['email']."</td></tr>
                <tr><td>Username</td><td>".$row['name']."</td></tr>
                <tr><td>Password</td><td>".$row['password']."</td></tr>
                <tr><td>Nama lengkap pengguna</td><td>".$row['namalengkap']."</td></tr>
                <tr><td>NIP pengguna</td><td>".$row['nip']."</td></tr>
                <tr><td>Jabatan pengguna</td><td>".$row['jabatan']."</td></tr>
                <tr><td>Kelompok pengguna</td><td>".$arr_ms_group[$row['ms_group_id']]."</td></tr>
                <tr><td>Login terakhir</td><td>".$row['lastlogin']."</td></tr>
                <tr><td>IP Login terakhir</td><td>".$row['lastloginip']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List persuratan_ms_tipesurat
     *
     **/
    function action_persuratan_ms_tipesurat()
    {

        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Tipesurat</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Tipesurat                  </div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_tipesurat/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."persuratan/ms_tipesurat/add/'\" title='Input Tipesurat baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Kode</span>
                                        <input type='text' class='form-control' name='filter[kode]' value='".(isset($this->post['filter']['kode'])?$this->post['filter']['kode']:null)."' placeholder='Input Kode'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Status</span>
                                        <input type='text' class='form-control' name='filter[name]' value='".(isset($this->post['filter']['name'])?$this->post['filter']['name']:null)."' placeholder='Input Status'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Status</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM persuratan_ms_tipesurat WHERE 1=1 {$this->groupfield}", array('kode','name'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT *  FROM persuratan_ms_tipesurat WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('kode','name'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."persuratan/ms_tipesurat/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."persuratan/ms_tipesurat/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."persuratan/ms_tipesurat");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of persuratan_ms_tipesurat
     *
     **/
    function action_persuratan_ms_tipesuratview($id) {
 
        $res = $this->db_query("SELECT *  FROM persuratan_ms_tipesurat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_tipesurat/' class='btn btn-default'><div>Daftar Tipesurat</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Tipesurat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Tipesurat
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_tipesurat/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$row['kode']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$row['name']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."persuratan/ms_tipesurat/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."persuratan/ms_tipesurat/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."persuratan/ms_tipesurat/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add persuratan_ms_tipesurat
     *
     **/
    function action_persuratan_ms_tipesuratadd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO persuratan_ms_tipesurat (kode,name,created,ipaddress) 
                VALUES ('".$this->post['kode']."','".$this->post['name']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."persuratan/ms_tipesurat");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
     
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_tipesurat/' class='btn btn-default'><div>Daftar Tipesurat</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Tipesurat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Tipesurat</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kode' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Status' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."persuratan/ms_tipesurat/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit persuratan_ms_tipesurat
     *
     **/
    function action_persuratan_ms_tipesuratedit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE persuratan_ms_tipesurat SET kode = '".$this->post['kode']."', name = '".$this->post['name']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."persuratan/ms_tipesurat");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
     
            $res = $this->db_query("SELECT *  FROM persuratan_ms_tipesurat WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                    <a href='".$this->basepath."persuratan/ms_tipesurat/' class='btn btn-default'><div>Daftar Tipesurat</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Tipesurat</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Tipesurat</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kode' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Status' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."persuratan/ms_tipesurat/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete persuratan_ms_tipesurat
     *
     **/
    function action_persuratan_ms_tipesuratdelete($id) {
        $res = $this->db_query("DELETE FROM persuratan_ms_tipesurat WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."persuratan/ms_tipesurat");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export persuratan_ms_tipesurat to HTML
     *
     **/
    function action_persuratan_ms_tipesurathtml()
    {
        $this->blankpage = true;

        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Tipesurat</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Kode</th>
                                <th>Status</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export persuratan_ms_tipesurat view to HTML
     *
     **/
    function action_persuratan_ms_tipesuratviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT *  FROM persuratan_ms_tipesurat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Tipesurat</h1>
            <table>
                <tr><td>Kode</td><td>".$row['kode']."</td></tr>
                <tr><td>Status</td><td>".$row['name']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List persuratan_ms_sifatsurat
     *
     **/
    function action_persuratan_ms_sifatsurat()
    {

        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Sifatsurat</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Sifatsurat                  </div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_sifatsurat/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."persuratan/ms_sifatsurat/add/'\" title='Input Sifatsurat baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Kode</span>
                                        <input type='text' class='form-control' name='filter[kode]' value='".(isset($this->post['filter']['kode'])?$this->post['filter']['kode']:null)."' placeholder='Input Kode'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Sifat surat</span>
                                        <input type='text' class='form-control' name='filter[name]' value='".(isset($this->post['filter']['name'])?$this->post['filter']['name']:null)."' placeholder='Input Sifat surat'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Sifat surat</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM persuratan_ms_sifatsurat WHERE 1=1 {$this->groupfield}", array('kode','name'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT *  FROM persuratan_ms_sifatsurat WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('kode','name'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."persuratan/ms_sifatsurat/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."persuratan/ms_sifatsurat/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."persuratan/ms_sifatsurat");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of persuratan_ms_sifatsurat
     *
     **/
    function action_persuratan_ms_sifatsuratview($id) {
 
        $res = $this->db_query("SELECT *  FROM persuratan_ms_sifatsurat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_sifatsurat/' class='btn btn-default'><div>Daftar Sifatsurat</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Sifatsurat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Sifatsurat
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_sifatsurat/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$row['kode']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Sifat surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$row['name']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."persuratan/ms_sifatsurat/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."persuratan/ms_sifatsurat/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."persuratan/ms_sifatsurat/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add persuratan_ms_sifatsurat
     *
     **/
    function action_persuratan_ms_sifatsuratadd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO persuratan_ms_sifatsurat (kode,name,created,ipaddress) 
                VALUES ('".$this->post['kode']."','".$this->post['name']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."persuratan/ms_sifatsurat");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
     
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_sifatsurat/' class='btn btn-default'><div>Daftar Sifatsurat</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Sifatsurat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Sifatsurat</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kode' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Sifat surat</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Sifat surat' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."persuratan/ms_sifatsurat/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit persuratan_ms_sifatsurat
     *
     **/
    function action_persuratan_ms_sifatsuratedit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE persuratan_ms_sifatsurat SET kode = '".$this->post['kode']."', name = '".$this->post['name']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."persuratan/ms_sifatsurat");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
     
            $res = $this->db_query("SELECT *  FROM persuratan_ms_sifatsurat WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                    <a href='".$this->basepath."persuratan/ms_sifatsurat/' class='btn btn-default'><div>Daftar Sifatsurat</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Sifatsurat</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Sifatsurat</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kode' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Sifat surat</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Sifat surat' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."persuratan/ms_sifatsurat/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete persuratan_ms_sifatsurat
     *
     **/
    function action_persuratan_ms_sifatsuratdelete($id) {
        $res = $this->db_query("DELETE FROM persuratan_ms_sifatsurat WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."persuratan/ms_sifatsurat");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export persuratan_ms_sifatsurat to HTML
     *
     **/
    function action_persuratan_ms_sifatsurathtml()
    {
        $this->blankpage = true;

        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Sifatsurat</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Kode</th>
                                <th>Sifat surat</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export persuratan_ms_sifatsurat view to HTML
     *
     **/
    function action_persuratan_ms_sifatsuratviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT *  FROM persuratan_ms_sifatsurat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Sifatsurat</h1>
            <table>
                <tr><td>Kode</td><td>".$row['kode']."</td></tr>
                <tr><td>Sifat surat</td><td>".$row['name']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List persuratan_ms_tujuanakhir
     *
     **/
    function action_persuratan_ms_tujuanakhir()
    {

        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Tujuanakhir</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Tujuanakhir                  </div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_tujuanakhir/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."persuratan/ms_tujuanakhir/add/'\" title='Input Tujuanakhir baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Kode</span>
                                        <input type='text' class='form-control' name='filter[kode]' value='".(isset($this->post['filter']['kode'])?$this->post['filter']['kode']:null)."' placeholder='Input Kode'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Status</span>
                                        <input type='text' class='form-control' name='filter[name]' value='".(isset($this->post['filter']['name'])?$this->post['filter']['name']:null)."' placeholder='Input Status'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode</th>
                                <th>Status</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM persuratan_ms_tujuanakhir WHERE 1=1 {$this->groupfield}", array('kode','name'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT *  FROM persuratan_ms_tujuanakhir WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('kode','name'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."persuratan/ms_tujuanakhir/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."persuratan/ms_tujuanakhir/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."persuratan/ms_tujuanakhir");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of persuratan_ms_tujuanakhir
     *
     **/
    function action_persuratan_ms_tujuanakhirview($id) {
 
        $res = $this->db_query("SELECT *  FROM persuratan_ms_tujuanakhir WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_tujuanakhir/' class='btn btn-default'><div>Daftar Tujuanakhir</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Tujuanakhir</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Tujuanakhir
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_tujuanakhir/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$row['kode']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$row['name']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."persuratan/ms_tujuanakhir/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."persuratan/ms_tujuanakhir/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."persuratan/ms_tujuanakhir/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add persuratan_ms_tujuanakhir
     *
     **/
    function action_persuratan_ms_tujuanakhiradd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO persuratan_ms_tujuanakhir (kode,name,created,ipaddress) 
                VALUES ('".$this->post['kode']."','".$this->post['name']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."persuratan/ms_tujuanakhir");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
     
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_tujuanakhir/' class='btn btn-default'><div>Daftar Tujuanakhir</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Tujuanakhir</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Tujuanakhir</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kode' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Status' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."persuratan/ms_tujuanakhir/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit persuratan_ms_tujuanakhir
     *
     **/
    function action_persuratan_ms_tujuanakhiredit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE persuratan_ms_tujuanakhir SET kode = '".$this->post['kode']."', name = '".$this->post['name']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."persuratan/ms_tujuanakhir");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
     
            $res = $this->db_query("SELECT *  FROM persuratan_ms_tujuanakhir WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                    <a href='".$this->basepath."persuratan/ms_tujuanakhir/' class='btn btn-default'><div>Daftar Tujuanakhir</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Tujuanakhir</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Tujuanakhir</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kode</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kode' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Status' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."persuratan/ms_tujuanakhir/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete persuratan_ms_tujuanakhir
     *
     **/
    function action_persuratan_ms_tujuanakhirdelete($id) {
        $res = $this->db_query("DELETE FROM persuratan_ms_tujuanakhir WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."persuratan/ms_tujuanakhir");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export persuratan_ms_tujuanakhir to HTML
     *
     **/
    function action_persuratan_ms_tujuanakhirhtml()
    {
        $this->blankpage = true;

        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Tujuanakhir</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Kode</th>
                                <th>Status</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export persuratan_ms_tujuanakhir view to HTML
     *
     **/
    function action_persuratan_ms_tujuanakhirviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT *  FROM persuratan_ms_tujuanakhir WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Tujuanakhir</h1>
            <table>
                <tr><td>Kode</td><td>".$row['kode']."</td></tr>
                <tr><td>Status</td><td>".$row['name']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List persuratan_ms_flowstat
     *
     **/
    function action_persuratan_ms_flowstat()
    {

        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Flowstat</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Flowstat                  </div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_flowstat/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."persuratan/ms_flowstat/add/'\" title='Input Flowstat baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Kelompok</span>
                                        <input type='text' class='form-control' name='filter[kode]' value='".(isset($this->post['filter']['kode'])?$this->post['filter']['kode']:null)."' placeholder='Input Kelompok'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Status Alur</span>
                                        <input type='text' class='form-control' name='filter[name]' value='".(isset($this->post['filter']['name'])?$this->post['filter']['name']:null)."' placeholder='Input Status Alur'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kelompok</th>
                                <th>Status Alur</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM persuratan_ms_flowstat WHERE 1=1 {$this->groupfield}", array('kode','name'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT *  FROM persuratan_ms_flowstat WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('kode','name'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."persuratan/ms_flowstat/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."persuratan/ms_flowstat/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."persuratan/ms_flowstat");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of persuratan_ms_flowstat
     *
     **/
    function action_persuratan_ms_flowstatview($id) {
 
        $res = $this->db_query("SELECT *  FROM persuratan_ms_flowstat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_flowstat/' class='btn btn-default'><div>Daftar Flowstat</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Flowstat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Flowstat
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/ms_flowstat/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kelompok</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$row['kode']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status Alur</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$row['name']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."persuratan/ms_flowstat/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."persuratan/ms_flowstat/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."persuratan/ms_flowstat/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add persuratan_ms_flowstat
     *
     **/
    function action_persuratan_ms_flowstatadd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO persuratan_ms_flowstat (kode,name,created,ipaddress) 
                VALUES ('".$this->post['kode']."','".$this->post['name']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."persuratan/ms_flowstat");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
     
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/ms_flowstat/' class='btn btn-default'><div>Daftar Flowstat</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Flowstat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Flowstat</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kelompok</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kelompok' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status Alur</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Status Alur' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."persuratan/ms_flowstat/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit persuratan_ms_flowstat
     *
     **/
    function action_persuratan_ms_flowstatedit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE persuratan_ms_flowstat SET kode = '".$this->post['kode']."', name = '".$this->post['name']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."persuratan/ms_flowstat");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
     
            $res = $this->db_query("SELECT *  FROM persuratan_ms_flowstat WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                    <a href='".$this->basepath."persuratan/ms_flowstat/' class='btn btn-default'><div>Daftar Flowstat</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Flowstat</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Flowstat</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Kelompok</label>
                            <div class='col-sm-7'>
                                <input name='kode' placeholder='Kelompok' value='".$row['kode']."' title='' id='input1' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Status Alur</label>
                            <div class='col-sm-7'>
                                <input name='name' placeholder='Status Alur' value='".$row['name']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."persuratan/ms_flowstat/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete persuratan_ms_flowstat
     *
     **/
    function action_persuratan_ms_flowstatdelete($id) {
        $res = $this->db_query("DELETE FROM persuratan_ms_flowstat WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."persuratan/ms_flowstat");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export persuratan_ms_flowstat to HTML
     *
     **/
    function action_persuratan_ms_flowstathtml()
    {
        $this->blankpage = true;

        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Flowstat</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Kelompok</th>
                                <th>Status Alur</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$row[kode]."</td>
                                <td>".$row[name]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export persuratan_ms_flowstat view to HTML
     *
     **/
    function action_persuratan_ms_flowstatviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT *  FROM persuratan_ms_flowstat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Flowstat</h1>
            <table>
                <tr><td>Kelompok</td><td>".$row['kode']."</td></tr>
                <tr><td>Status Alur</td><td>".$row['name']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List persuratan_nomorsurat
     *
     **/
    function action_persuratan_nomorsurat()
    {

        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Nomorsurat</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Nomorsurat                  </div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/nomorsurat/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."persuratan/nomorsurat/add/'\" title='Input Nomorsurat baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tipe surat</span>
                                        <select class='form-control' name='filter[ms_tipesurat_id]'>
                                            <option value=''>-- Pilih Tipe surat--</option>";
            while (list($key,$val) = each($arr_ms_tipesurat)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tahun surat</span>
                                        <input type='text' class='form-control' name='filter[year]' value='".(isset($this->post['filter']['year'])?$this->post['filter']['year']:null)."' placeholder='Input Tahun surat'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Nomor berikutnya</span>
                                        <input type='text' class='form-control' name='filter[nextid]' value='".(isset($this->post['filter']['nextid'])?$this->post['filter']['nextid']:null)."' placeholder='Input Nomor berikutnya'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tipe surat</th>
                                <th>Tahun surat</th>
                                <th>Nomor berikutnya</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM persuratan_nomorsurat WHERE 1=1 {$this->groupfield}", array('ms_tipesurat_id','year','nextid'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT *  FROM persuratan_nomorsurat WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('ms_tipesurat_id','year','nextid'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$arr_ms_tipesurat[$row[ms_tipesurat_id]]."</td>
                                <td>".$row[year]."</td>
                                <td>".number_format($row[nextid])."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."persuratan/nomorsurat/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."persuratan/nomorsurat/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."persuratan/nomorsurat");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of persuratan_nomorsurat
     *
     **/
    function action_persuratan_nomorsuratview($id) {
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
 
        $res = $this->db_query("SELECT *  FROM persuratan_nomorsurat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/nomorsurat/' class='btn btn-default'><div>Daftar Nomorsurat</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Nomorsurat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Nomorsurat
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/nomorsurat/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$arr_ms_tipesurat[$row['ms_tipesurat_id']]."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tahun surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$row['year']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Nomor berikutnya</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input3' class='form-control' readonly value='".number_format($row['nextid'])."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."persuratan/nomorsurat/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."persuratan/nomorsurat/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."persuratan/nomorsurat/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add persuratan_nomorsurat
     *
     **/
    function action_persuratan_nomorsuratadd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO persuratan_nomorsurat (ms_tipesurat_id,year,nextid,created,ipaddress) 
                VALUES ('".$this->post['ms_tipesurat_id']."','".$this->post['year']."','".$this->post['nextid']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."persuratan/nomorsurat");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
            $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
 
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/nomorsurat/' class='btn btn-default'><div>Daftar Nomorsurat</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Nomorsurat</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Nomorsurat</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tipesurat_id' id='input1'>
                                    <option value=''>-- Pilih tipe surat--</option>";
                while (list($key,$val) = each($arr_ms_tipesurat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tahun surat</label>
                            <div class='col-sm-7'>
                                <input name='year' placeholder='Tahun surat' value='".$row['year']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Nomor berikutnya</label>
                            <div class='col-sm-7'>
                                <input name='nextid' placeholder='Nomor berikutnya' value='".$row['nextid']."' title='' id='input3' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."persuratan/nomorsurat/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit persuratan_nomorsurat
     *
     **/
    function action_persuratan_nomorsuratedit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE persuratan_nomorsurat SET ms_tipesurat_id = '".$this->post['ms_tipesurat_id']."', year = '".$this->post['year']."', nextid = '".$this->post['nextid']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."persuratan/nomorsurat");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
            $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
 
            $res = $this->db_query("SELECT *  FROM persuratan_nomorsurat WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                    <a href='".$this->basepath."persuratan/nomorsurat/' class='btn btn-default'><div>Daftar Nomorsurat</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Nomorsurat</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Nomorsurat</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tipesurat_id' id='input1'>
                                    <option value=''>-- Pilih tipe surat--</option>";
                while (list($key,$val) = each($arr_ms_tipesurat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tahun surat</label>
                            <div class='col-sm-7'>
                                <input name='year' placeholder='Tahun surat' value='".$row['year']."' title='' id='input2' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Nomor berikutnya</label>
                            <div class='col-sm-7'>
                                <input name='nextid' placeholder='Nomor berikutnya' value='".$row['nextid']."' title='' id='input3' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."persuratan/nomorsurat/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete persuratan_nomorsurat
     *
     **/
    function action_persuratan_nomorsuratdelete($id) {
        $res = $this->db_query("DELETE FROM persuratan_nomorsurat WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."persuratan/nomorsurat");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export persuratan_nomorsurat to HTML
     *
     **/
    function action_persuratan_nomorsurathtml()
    {
        $this->blankpage = true;

        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Nomorsurat</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Tipe surat</th>
                                <th>Tahun surat</th>
                                <th>Nomor berikutnya</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$arr_ms_tipesurat[$row[ms_tipesurat_id]]."</td>
                                <td>".$row[year]."</td>
                                <td>".number_format($row[nextid])."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export persuratan_nomorsurat view to HTML
     *
     **/
    function action_persuratan_nomorsuratviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT *  FROM persuratan_nomorsurat WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Nomorsurat</h1>
            <table>
                <tr><td>Tipe surat</td><td>".$arr_ms_tipesurat[$row['ms_tipesurat_id']]."</td></tr>
                <tr><td>Tahun surat</td><td>".$row['year']."</td></tr>
                <tr><td>Nomor berikutnya</td><td>".number_format($row['nextid'])."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List persuratan_suratmasuk
     *
     **/
    function action_persuratan_suratmasuk()
    {

        $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_sifatsurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_sifatsurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tujuanakhir");
        while ( $row = $this->db_fetch() ) { $arr_ms_tujuanakhir[$row['id']] = $row['name']; }
        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Suratmasuk</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Suratmasuk                  </div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/suratmasuk/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."persuratan/suratmasuk/add/'\" title='Input Suratmasuk baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Status alur</span>
                                        <select class='form-control' name='filter[ms_flowstat_id]'>
                                            <option value=''>-- Pilih Status alur--</option>";
            while (list($key,$val) = each($arr_ms_flowstat)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_flowstat_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tipe surat</span>
                                        <select class='form-control' name='filter[ms_tipesurat_id]'>
                                            <option value=''>-- Pilih Tipe surat--</option>";
            while (list($key,$val) = each($arr_ms_tipesurat)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Sifat surat</span>
                                        <select class='form-control' name='filter[ms_sifatsurat_id]'>
                                            <option value=''>-- Pilih Sifat surat--</option>";
            while (list($key,$val) = each($arr_ms_sifatsurat)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_sifatsurat_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tujuan akhir</span>
                                        <select class='form-control' name='filter[ms_tujuanakhir_id]'>
                                            <option value=''>-- Pilih Tujuan akhir--</option>";
            while (list($key,$val) = each($arr_ms_tujuanakhir)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_tujuanakhir_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Nomor agenda</span>
                                        <input type='text' class='form-control' name='filter[noagenda]' value='".(isset($this->post['filter']['noagenda'])?$this->post['filter']['noagenda']:null)."' placeholder='Input Nomor agenda'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tanggal agenda</span>

			                            <div class='col-sm-7'>
			                                <div class='controls input-append date form_date' data-date='".(isset($this->post['filter']['tglagenda'])?$this->post['filter']['tglagenda']:null)."' data-date-format='dd-mm-yyyy' data-link-field='input6' data-link-format='dd-mm-yyyy'>
			                                    <input size='16' type='text' value='".(isset($this->post['filter']['tglagenda'])?$this->post['filter']['tglagenda']:null)."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
			                                </div>
			                                <input title='input dengan format (dd-mm-yyyy)' id='input6' type='hidden' class='form-control' name='filter[tglagenda]' value='".(isset($this->post['filter']['tglagenda'])?$this->post['filter']['tglagenda']:null)."' placeholder='(dd-mm-yyyy) Input Tanggal agenda'>
			                            </div>

                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Nomor surat</span>
                                        <input type='text' class='form-control' name='filter[nosurat]' value='".(isset($this->post['filter']['nosurat'])?$this->post['filter']['nosurat']:null)."' placeholder='Input Nomor surat'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tanggal surat</span>

			                            <div class='col-sm-7'>
			                                <div class='controls input-append date form_date' data-date='".(isset($this->post['filter']['tglsurat'])?$this->post['filter']['tglsurat']:null)."' data-date-format='dd-mm-yyyy' data-link-field='input6' data-link-format='dd-mm-yyyy'>
			                                    <input size='16' type='text' value='".(isset($this->post['filter']['tglsurat'])?$this->post['filter']['tglsurat']:null)."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
			                                </div>
			                                <input title='input dengan format (dd-mm-yyyy)' id='input6' type='hidden' class='form-control' name='filter[tglsurat]' value='".(isset($this->post['filter']['tglsurat'])?$this->post['filter']['tglsurat']:null)."' placeholder='(dd-mm-yyyy) Input Tanggal agenda'>
			                            </div>
			                            
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Asal surat</span>
                                        <input type='text' class='form-control' name='filter[asalsurat]' value='".(isset($this->post['filter']['asalsurat'])?$this->post['filter']['asalsurat']:null)."' placeholder='Input Asal surat'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Perihal</span>
                                        <input type='text' class='form-control' name='filter[perihal]' value='".(isset($this->post['filter']['perihal'])?$this->post['filter']['perihal']:null)."' placeholder='Input Perihal'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Nama pengirim</span>
                                        <input type='text' class='form-control' name='filter[namapengirim]' value='".(isset($this->post['filter']['namapengirim'])?$this->post['filter']['namapengirim']:null)."' placeholder='Input Nama pengirim'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tujuan/Kepala surat</span>
                                        <input type='text' class='form-control' name='filter[tujuansurat]' value='".(isset($this->post['filter']['tujuansurat'])?$this->post['filter']['tujuansurat']:null)."' placeholder='Input Tujuan/Kepala surat'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Isi disposisi pimpinan</span>
                                        <input type='text' class='form-control' name='filter[disposisipimpinan]' value='".(isset($this->post['filter']['disposisipimpinan'])?$this->post['filter']['disposisipimpinan']:null)."' placeholder='Input Isi disposisi pimpinan'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Lampiran</span>
                                        <input type='text' class='form-control' name='filter[lampiran]' value='".(isset($this->post['filter']['lampiran'])?$this->post['filter']['lampiran']:null)."' placeholder='Input Lampiran'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor agenda</th>
                                <th>Tanggal agenda</th>
                                <th>Nomor surat</th>
                                <th>Tanggal surat</th>
                                <th>Asal surat</th>
                                <th>Perihal</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM persuratan_suratmasuk WHERE 1=1 {$this->groupfield}", array('ms_flowstat_id','ms_tipesurat_id','ms_sifatsurat_id','ms_tujuanakhir_id','noagenda','tglagenda','nosurat','tglsurat','asalsurat','perihal','namapengirim','tujuansurat','disposisipimpinan','lampiran'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratmasuk WHERE 1=1 {$this->groupfield} ORDER BY persuratan_suratmasuk.tglagenda DESC, id DESC LIMIT {$limit},{$perpage}", array('ms_flowstat_id','ms_tipesurat_id','ms_sifatsurat_id','ms_tujuanakhir_id','noagenda','tglagenda','nosurat','tglsurat','asalsurat','perihal','namapengirim','tujuansurat','disposisipimpinan','lampiran'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[noagenda]."</td>
                                <td>".$row[tglagenda]."</td>
                                <td>".$row[nosurat]."</td>
                                <td>".$row[tglsurat]."</td>
                                <td>".$row[asalsurat]."</td>
                                <td>".$row[perihal]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."persuratan/suratmasuk/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."persuratan/suratmasuk/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."persuratan/suratmasuk");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of persuratan_suratmasuk
     *
     **/
    function action_persuratan_suratmasukview($id) {
        $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_sifatsurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_sifatsurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tujuanakhir");
        while ( $row = $this->db_fetch() ) { $arr_ms_tujuanakhir[$row['id']] = $row['name']; }
 
        $res = $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratmasuk WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-default'><div>Daftar Suratmasuk</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Suratmasuk</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Suratmasuk
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/suratmasuk/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Status alur</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$arr_ms_flowstat[$row['ms_flowstat_id']]."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$arr_ms_tipesurat[$row['ms_tipesurat_id']]."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Sifat surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input3' class='form-control' readonly value='".$arr_ms_sifatsurat[$row['ms_sifatsurat_id']]."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Tujuan akhir</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input4' class='form-control' readonly value='".$arr_ms_tujuanakhir[$row['ms_tujuanakhir_id']]."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>Nomor agenda</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input5' class='form-control' readonly value='".$row['noagenda']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Tanggal agenda</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input6' class='form-control' readonly value='".$row['tglagenda']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input7' class='col-sm-5 control-label'>Nomor surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input7' class='form-control' readonly value='".$row['nosurat']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input8' class='col-sm-5 control-label'>Tanggal surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input8' class='form-control' readonly value='".$row['tglsurat']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input9' class='col-sm-5 control-label'>Asal surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input9' class='form-control' readonly value='".$row['asalsurat']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input10' class='col-sm-5 control-label'>Perihal</label>
                            <div class='col-sm-7'>
                                <textarea id='input10' class='form-control' readonly>".$row['perihal']."</textarea>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input11' class='col-sm-5 control-label'>Nama pengirim</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input11' class='form-control' readonly value='".$row['namapengirim']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input12' class='col-sm-5 control-label'>Tujuan/Kepala surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input12' class='form-control' readonly value='".$row['tujuansurat']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input13' class='col-sm-5 control-label'>Isi disposisi pimpinan</label>
                            <div class='col-sm-7'>
                                <textarea id='input13' class='form-control' readonly>".$row['disposisipimpinan']."</textarea>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input14' class='col-sm-5 control-label'>Lampiran</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input14' class='form-control' readonly value='".$row['lampiran']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."persuratan/suratmasuk/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."persuratan/suratmasuk/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add persuratan_suratmasuk
     *
     **/
    function action_persuratan_suratmasukadd($id) { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $this->suratmasukadd();
        }  
            else  
        {
            $id = (empty($id) ? 1 : $id);

            $this->db_query("SELECT * FROM persuratan_ms_flowstat");
            while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
            $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
            while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
            $this->db_query("SELECT * FROM persuratan_ms_sifatsurat");
            while ( $row = $this->db_fetch() ) { $arr_ms_sifatsurat[$row['id']] = $row['name']; }
            $this->db_query("SELECT * FROM persuratan_ms_tujuanakhir");
            while ( $row = $this->db_fetch() ) { $arr_ms_tujuanakhir[$row['id']] = $row['name']; }
            $this->db_query("SELECT * FROM persuratan_ms_arsip");
            while ( $row = $this->db_fetch() ) { $arr_ms_arsip[$row['id']] = $row['name']; }
 
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-default'><div>Daftar Suratmasuk</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Suratmasuk</div></a>
            </div>

            ".(!empty($_SESSION['last_noagenda']) ? "<br><div class='alert alert-warning alert-dismissable'>
                <button type='button' class='close' data-dismiss='alert' aria-hidden='true'>&times;</button>
                <b>PERHATIAN</b> : ".$_SESSION['last_nosurat']." berhasil ditambahkan dengan agenda ".$_SESSION['last_noagenda']."
                </div>" : "")."

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Surat masuk</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tipesurat_id' id='input2' readonly>
                                    <option value=''>-- Pilih tipe surat--</option>";
                while (list($key,$val) = each($arr_ms_tipesurat)) {
                    $this->v .= "<option value='".$key."'".($key==$id?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Sifat surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_sifatsurat_id' id='input3'>
                                    <option value=''>-- Pilih sifat surat--</option>";
                while (list($key,$val) = each($arr_ms_sifatsurat)) {
                    $this->v .= "<option value='".$key."'".($key==1?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input9' class='col-sm-5 control-label'>Asal surat</label>
                            <div class='col-sm-7'>
                                <input name='asalsurat' placeholder='Asal surat' value='".$row['asalsurat']."' title='' id='inputasalsurat' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input8' class='col-sm-5 control-label'>Tanggal Agenda</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_date' data-date='".$row['tglagenda']."' data-date-format='dd-mm-yyyy' data-link-field='input8' data-link-format='dd-mm-yyyy'>
                                    <input size='16' type='text' value='".$row['tglagenda']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='tglagenda' placeholder='dd-mm-yyyy' value='".$row['tglagenda']."' title='input dengan format (dd-mm-yyyy)' id='input8' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input7' class='col-sm-5 control-label'>Nomor surat</label>
                            <div class='col-sm-7'>
                                <input name='nosurat' placeholder='Nomor surat' value='".$row['nosurat']."' title='' id='inputnosurat' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input8' class='col-sm-5 control-label'>Tanggal surat</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_date' data-date='".$row['tglsurat']."' data-date-format='dd-mm-yyyy' data-link-field='input88' data-link-format='dd-mm-yyyy'>
                                    <input size='16' type='text' value='".$row['tglsurat']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='tglsurat' placeholder='dd-mm-yyyy' value='".$row['tglsurat']."' title='input dengan format (dd-mm-yyyy)' id='input88' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input10' class='col-sm-5 control-label'>Perihal</label>
                            <div class='col-sm-7'>
                                <textarea name='perihal' placeholder='Perihal' id='input10' class='form-control'>".$row['perihal']."</textarea>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input11' class='col-sm-5 control-label'>Nama pengirim</label>
                            <div class='col-sm-7'>
                                <input name='namapengirim' placeholder='Nama pengirim' value='".$row['namapengirim']."' title='' id='input11' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input12' class='col-sm-5 control-label'>Tujuan surat</label>
                            <div class='col-sm-7'>
                                <input name='tujuansurat' placeholder='Tujuan surat' value='".$row['tujuansurat']."' title='' id='input12' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Tujuan akhir</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tujuanakhir_id' id='input4'>
                                    <option value=''>-- Pilih tujuan akhir--</option>";
                while (list($key,$val) = each($arr_ms_tujuanakhir)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_tujuanakhir_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Status surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_arsip_id' id='input4'>
                                    <option value=''>-- Pilih --</option>";
                while (list($key,$val) = each($arr_ms_arsip)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_arsip_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input13' class='col-sm-5 control-label'>Isi disposisi pimpinan</label>
                            <div class='col-sm-7'>
                                <textarea name='disposisipimpinan' placeholder='Isi disposisi pimpinan' id='input13' class='form-control'>".$row['disposisipimpinan']."</textarea>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            <script type='text/javascript'>document.getElementById('inputasalsurat').focus();</script>
            ";
        }
    }

    /**
     *
     * Edit persuratan_suratmasuk
     *
     **/
    function action_persuratan_suratmasukedit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE persuratan_suratmasuk SET ms_flowstat_id = '".$this->post['ms_flowstat_id']."', ms_tipesurat_id = '".$this->post['ms_tipesurat_id']."', ms_sifatsurat_id = '".$this->post['ms_sifatsurat_id']."', ms_tujuanakhir_id = '".$this->post['ms_tujuanakhir_id']."', noagenda = '".$this->post['noagenda']."', tglagenda = STR_TO_DATE('".$this->post['tglagenda']."', '%d-%m-%Y'), nosurat = '".$this->post['nosurat']."', tglsurat = STR_TO_DATE('".$this->post['tglsurat']."', '%d-%m-%Y'), asalsurat = '".$this->post['asalsurat']."', perihal = '".$this->post['perihal']."', namapengirim = '".$this->post['namapengirim']."', tujuansurat = '".$this->post['tujuansurat']."', disposisipimpinan = '".$this->post['disposisipimpinan']."', lampiran = '".$this->post['lampiran']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."persuratan/suratmasuk");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
            $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_sifatsurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_sifatsurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tujuanakhir");
        while ( $row = $this->db_fetch() ) { $arr_ms_tujuanakhir[$row['id']] = $row['name']; }
 
            $res = $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratmasuk WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                    <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-default'><div>Daftar Suratmasuk</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Suratmasuk</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Suratmasuk</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Status alur</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_flowstat_id' id='input1'>
                                    <option value=''>-- Pilih status alur--</option>";
                while (list($key,$val) = each($arr_ms_flowstat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_flowstat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tipesurat_id' id='input2'>
                                    <option value=''>-- Pilih tipe surat--</option>";
                while (list($key,$val) = each($arr_ms_tipesurat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Sifat surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_sifatsurat_id' id='input3'>
                                    <option value=''>-- Pilih sifat surat--</option>";
                while (list($key,$val) = each($arr_ms_sifatsurat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_sifatsurat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Tujuan akhir</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tujuanakhir_id' id='input4'>
                                    <option value=''>-- Pilih tujuan akhir--</option>";
                while (list($key,$val) = each($arr_ms_tujuanakhir)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_tujuanakhir_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>Nomor agenda</label>
                            <div class='col-sm-7'>
                                <input name='noagenda' placeholder='Nomor agenda' value='".$row['noagenda']."' title='' id='input5' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Tanggal agenda</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_date' data-date='".$row['tglagenda']."' data-date-format='dd-mm-yyyy' data-link-field='input6' data-link-format='dd-mm-yyyy'>
                                    <input size='16' type='text' value='".$row['tglagenda']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='tglagenda' placeholder='dd-mm-yyyy' value='".$row['tglagenda']."' title='input dengan format (dd-mm-yyyy)' id='input6' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input7' class='col-sm-5 control-label'>Nomor surat</label>
                            <div class='col-sm-7'>
                                <input name='nosurat' placeholder='Nomor surat' value='".$row['nosurat']."' title='' id='input7' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input8' class='col-sm-5 control-label'>Tanggal surat</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_date' data-date='".$row['tglsurat']."' data-date-format='dd-mm-yyyy' data-link-field='input8' data-link-format='dd-mm-yyyy'>
                                    <input size='16' type='text' value='".$row['tglsurat']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='tglsurat' placeholder='dd-mm-yyyy' value='".$row['tglsurat']."' title='input dengan format (dd-mm-yyyy)' id='input8' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input9' class='col-sm-5 control-label'>Asal surat</label>
                            <div class='col-sm-7'>
                                <input name='asalsurat' placeholder='Asal surat' value='".$row['asalsurat']."' title='' id='input9' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input10' class='col-sm-5 control-label'>Perihal</label>
                            <div class='col-sm-7'>
                                <textarea name='perihal' placeholder='Perihal' id='input10' class='form-control'>".$row['perihal']."</textarea>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input11' class='col-sm-5 control-label'>Nama pengirim</label>
                            <div class='col-sm-7'>
                                <input name='namapengirim' placeholder='Nama pengirim' value='".$row['namapengirim']."' title='' id='input11' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input12' class='col-sm-5 control-label'>Tujuan/Kepala surat</label>
                            <div class='col-sm-7'>
                                <input name='tujuansurat' placeholder='Tujuan/Kepala surat' value='".$row['tujuansurat']."' title='' id='input12' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input13' class='col-sm-5 control-label'>Isi disposisi pimpinan</label>
                            <div class='col-sm-7'>
                                <textarea name='disposisipimpinan' placeholder='Isi disposisi pimpinan' id='input13' class='form-control'>".$row['disposisipimpinan']."</textarea>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input14' class='col-sm-5 control-label'>Lampiran</label>
                            <div class='col-sm-7'>
                                <input name='lampiran' placeholder='Lampiran' value='".$row['lampiran']."' title='' id='input14' type='file' class='form-control filestyle'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete persuratan_suratmasuk
     *
     **/
    function action_persuratan_suratmasukdelete($id) {
        $res = $this->db_query("DELETE FROM persuratan_suratmasuk WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."persuratan/suratmasuk");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export persuratan_suratmasuk to HTML
     *
     **/
    function action_persuratan_suratmasukhtml()
    {
        $this->blankpage = true;

        $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_sifatsurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_sifatsurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tujuanakhir");
        while ( $row = $this->db_fetch() ) { $arr_ms_tujuanakhir[$row['id']] = $row['name']; }
        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Suratmasuk</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Status alur</th>
                                <th>Tipe surat</th>
                                <th>Sifat surat</th>
                                <th>Tujuan akhir</th>
                                <th>Nomor agenda</th>
                                <th>Tanggal agenda</th>
                                <th>Nomor surat</th>
                                <th>Tanggal surat</th>
                                <th>Asal surat</th>
                                <th>Perihal</th>
                                <th>Nama pengirim</th>
                                <th>Tujuan/Kepala surat</th>
                                <th>Isi disposisi pimpinan</th>
                                <th>Lampiran</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$arr_ms_flowstat[$row[ms_flowstat_id]]."</td>
                                <td>".$arr_ms_tipesurat[$row[ms_tipesurat_id]]."</td>
                                <td>".$arr_ms_sifatsurat[$row[ms_sifatsurat_id]]."</td>
                                <td>".$arr_ms_tujuanakhir[$row[ms_tujuanakhir_id]]."</td>
                                <td>".$row[noagenda]."</td>
                                <td>".$row[tglagenda]."</td>
                                <td>".$row[nosurat]."</td>
                                <td>".$row[tglsurat]."</td>
                                <td>".$row[asalsurat]."</td>
                                <td>".$row[perihal]."</td>
                                <td>".$row[namapengirim]."</td>
                                <td>".$row[tujuansurat]."</td>
                                <td>".$row[disposisipimpinan]."</td>
                                <td>".$row[lampiran]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export persuratan_suratmasuk view to HTML
     *
     **/
    function action_persuratan_suratmasukviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratmasuk WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Suratmasuk</h1>
            <table>
                <tr><td>Status alur</td><td>".$arr_ms_flowstat[$row['ms_flowstat_id']]."</td></tr>
                <tr><td>Tipe surat</td><td>".$arr_ms_tipesurat[$row['ms_tipesurat_id']]."</td></tr>
                <tr><td>Sifat surat</td><td>".$arr_ms_sifatsurat[$row['ms_sifatsurat_id']]."</td></tr>
                <tr><td>Tujuan akhir</td><td>".$arr_ms_tujuanakhir[$row['ms_tujuanakhir_id']]."</td></tr>
                <tr><td>Nomor agenda</td><td>".$row['noagenda']."</td></tr>
                <tr><td>Tanggal agenda</td><td>".$row['tglagenda']."</td></tr>
                <tr><td>Nomor surat</td><td>".$row['nosurat']."</td></tr>
                <tr><td>Tanggal surat</td><td>".$row['tglsurat']."</td></tr>
                <tr><td>Asal surat</td><td>".$row['asalsurat']."</td></tr>
                <tr><td>Perihal</td><td>".$row['perihal']."</td></tr>
                <tr><td>Nama pengirim</td><td>".$row['namapengirim']."</td></tr>
                <tr><td>Tujuan/Kepala surat</td><td>".$row['tujuansurat']."</td></tr>
                <tr><td>Isi disposisi pimpinan</td><td>".$row['disposisipimpinan']."</td></tr>
                <tr><td>Lampiran</td><td>".$row['lampiran']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * List persuratan_suratkeluar
     *
     **/
    function action_persuratan_suratkeluar()
    {

        $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='#' class='btn btn-default'><div>Daftar Suratkeluar</div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Daftar Suratkeluar</div>
                  <div class='col-md-6 text-right'>
                    <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/suratkeluar/html/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                    <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                    <button class='btn btn-primary btn-sm' data-toggle='modal' data-target='.bs-example-modal-lg'><span class='glyphicon glyphicon-search'></span> Pencarian</button>&nbsp;
                    <button class='btn btn-primary btn-sm' onclick=\"location.href='".$this->basepath."persuratan/suratkeluar/add/'\" title='Input Suratkeluar baru'><span class='glyphicon glyphicon-plus-sign'></span> Tambah data</button>
                  </div>
                </div>
                <div class='modal fade bs-example-modal-lg' tabindex='-1' role='dialog' aria-labelledby='myLargeModalLabel' aria-hidden='true'>
                  <div class='modal-dialog modal-lg'>
                    <div class='modal-content'>
                        <form class='form-horizontal' role='form' method='post' id='filter-form'>
                          <div class='modal-header'>
                            <button type='button' class='close' data-dismiss='modal' aria-hidden='true'>&times;</button>
                            <h4 class='modal-title text-left' id='myModalLabel'><span class='glyphicon glyphicon-search'></span> Pencarian data</h4>
                          </div>
                          <div class='modal-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Status alur</span>
                                        <select class='form-control' name='filter[ms_flowstat_id]'>
                                            <option value=''>-- Pilih Status alur--</option>";
            while (list($key,$val) = each($arr_ms_flowstat)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_flowstat_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tipe surat</span>
                                        <select class='form-control' name='filter[ms_tipesurat_id]'>
                                            <option value=''>-- Pilih Tipe surat--</option>";
            while (list($key,$val) = each($arr_ms_tipesurat)) {
                $this->v .= "<option value='".$key."'".($key==$this->post['filter']['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
            }
            $this->v .= "
                                        </select>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Nomor surat</span>
                                        <input type='text' class='form-control' name='filter[nomorsurat]' value='".(isset($this->post['filter']['nomorsurat'])?$this->post['filter']['nomorsurat']:null)."' placeholder='Input Nomor surat'>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tanggal</span>
                                        <input type='text' class='form-control' name='filter[tglsurat]' value='".(isset($this->post['filter']['tglsurat'])?$this->post['filter']['tglsurat']:null)."' placeholder='(dd-mm-yyyy) Input Tanggal'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Perihal</span>
                                        <input type='text' class='form-control' name='filter[perihal]' value='".(isset($this->post['filter']['perihal'])?$this->post['filter']['perihal']:null)."' placeholder='Input Perihal'>
                                    </div>
                                    <div class='input-group'>
                                        <span class='input-group-addon'>Tujuan</span>
                                        <input type='text' class='form-control' name='filter[tujuan]' value='".(isset($this->post['filter']['tujuan'])?$this->post['filter']['tujuan']:null)."' placeholder='Input Tujuan'>
                                    </div>
                                </div>
                            </div>
                              <input type='hidden' id='per-page' name='perpage' value=".(isset($this->post['perpage'])?$this->post['perpage']:5).">
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-default' data-dismiss='modal'>Tutup</button>&nbsp;
                            <button type='submit' class='btn btn-primary' id='btn-filter'><span class='glyphicon glyphicon-search'></span> Cari</button>
                          </div>
                        </form>
                    </div>
                  </div>
                </div>
            </div>
            <!-- List group -->
            <ul class='list-group'>
                <!-- Table -->
                <li class='list-group-item'>
                    <table class='table table-striped table-hover'>
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nomor surat</th>
                                <th>Tanggal</th>
                                <th>Perihal</th>
                                <th>Tujuan</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT COUNT(*) AS jml FROM persuratan_suratkeluar WHERE 1=1 {$this->groupfield}", array('ms_flowstat_id','ms_tipesurat_id','nomorsurat','tglsurat','perihal','tujuan'));
            $row = $this->db_fetch();
            $datacount  = $row['jml'];
            $perpage    = (isset($this->post['perpage'])?$this->post['perpage']:5);
            $pagecount  = ceil($datacount / $perpage);
            $page       = (isset($this->post['page']) ? $this->post['page'] : (isset($this->URI[ACTION_PARAM_IDX+1]) ? $this->URI[ACTION_PARAM_IDX+1] : 1));
            $limit      = ($page-1)*$perpage;
            $this->save_query = true;
            $this->db_query("SELECT * , DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratkeluar WHERE 1=1 {$this->groupfield} LIMIT {$limit},{$perpage}", array('ms_flowstat_id','ms_tipesurat_id','nomorsurat','tglsurat','perihal','tujuan'));
            $no = (($page-1)*$perpage)+1;
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[nomorsurat]."</td>
                                <td>".$row[tglsurat]."</td>
                                <td>".$row[perihal]."</td>
                                <td>".$row[tujuan]."</td>
                                <td nowrap>
                                    <a href='".$this->basepath."persuratan/suratkeluar/view/".$row["id"]."/' title='Lihat' class='btn btn-default btn-xs glyphicon glyphicon-eye-open'></a>&nbsp;
                                    <a href='".$this->basepath."persuratan/suratkeluar/edit/".$row["id"]."/' title='Ubah' class='btn btn-default btn-xs glyphicon glyphicon-pencil'></a>
                                </td>
                            </tr>";
                $no++;
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
                <!-- Pagination -->
                <li class='list-group-item text-right'>
                    <div class='container-fluid'>
                        <div class='row'>
                          <div class='col-md-6 text-left'>
                            ".$datacount." record(s) | show <select id='sel-perpage' onchange='perpage(this); return false;'><option value='5'>5</option><option value='10'>10</option><option value='20'>20</option></select> records perpage
                          </div>
                          <div class='col-md-6 text-right'>";
                $this->v .= $this->pagination($datacount , $perpage, $page, $url=$this->basepath."persuratan/suratkeluar");
                $this->v .= "
                          </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <script>filter=function(el){".chr(36)."('#filter-form').attr('action',".chr(36)."(el).attr('href')); ".chr(36)."('#btn-filter').click();}; perpage=function(el){".chr(36)."('#per-page').val(".chr(36)."(el).val()); ".chr(36)."('#btn-filter').click();};</script>
        <style>
        .pagination {
            border-radius: 4px;
            display: inline-block;
            margin: 0;
            padding-left: 0;
        }
        </style>
        ";
    }
    /**
     *
     * View detail of persuratan_suratkeluar
     *
     **/
    function action_persuratan_suratkeluarview($id) {
        $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
 
        $res = $this->db_query("SELECT * , DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratkeluar WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/suratkeluar/' class='btn btn-default'><div>Daftar Suratkeluar</div></a>
                <a href='#' class='btn btn-default'><div>Lihat Data Suratkeluar</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading container-fluid'>
                    <div class='row'>
                      <div class='col-md-6'>
                        <span class='glyphicon glyphicon-pencil'></span> Lihat Data Suratkeluar
                      </div>
                      <div class='col-md-6 text-right'>
                        <a class='btn btn-success btn-sm' href='".$this->basepath."persuratan/suratkeluar/viewhtml/{$id}/' title='ekspor ke HTML' target='_blank'><i class='fa fa fa-file-word-o fa-lg'></i></a>
                        <a class='btn btn-success btn-sm' href='#' title='ekspor ke Excel'><i class='fa fa fa-file-excel-o fa-lg'></i></a>
                      </div>
                    </div>
                </div>
                <div class='panel-body'>
                    <form class='form-horizontal' role='form' method='POST'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Status alur</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input1' class='form-control' readonly value='".$arr_ms_flowstat[$row['ms_flowstat_id']]."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input2' class='form-control' readonly value='".$arr_ms_tipesurat[$row['ms_tipesurat_id']]."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Nomor surat</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input3' class='form-control' readonly value='".$row['nomorsurat']."'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Tanggal</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input4' class='form-control' readonly value='".$row['tglsurat']."'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>Perihal</label>
                            <div class='col-sm-7'>
                                <textarea id='input5' class='form-control' readonly>".$row['perihal']."</textarea>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Tujuan</label>
                            <div class='col-sm-7'>
                                <input type='text' id='input6' class='form-control' readonly value='".$row['tujuan']."'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                    </form>
                </div>
                <div class='panel-footer text-right'>
                    <a href='".$this->basepath."persuratan/suratkeluar/edit/".$id."/' class='btn btn-default'>Edit</a>&nbsp;
                    <a href='#' onClick=\"if(confirm('Apakah anda yakin untuk menghapus data ini ?')){location.href='".$this->basepath."persuratan/suratkeluar/delete/".$id."/';}\" class='btn btn-danger'>Hapus</a>&nbsp;
                    <a href='".$this->basepath."persuratan/suratkeluar/' class='btn btn-primary'>Kembali ke daftar</a>
                </div>
            </div>
            ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
    }

    /**
     *
     * Add persuratan_suratkeluar
     *
     **/
    function action_persuratan_suratkeluaradd() { 
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("INSERT INTO persuratan_suratkeluar (ms_flowstat_id,ms_tipesurat_id,nomorsurat,tglsurat,perihal,tujuan,created,ipaddress) 
                VALUES ('".$this->post['ms_flowstat_id']."','".$this->post['ms_tipesurat_id']."','".$this->post['nomorsurat']."',STR_TO_DATE('".$this->post['tglsurat']."', '%d-%m-%Y'),'".$this->post['perihal']."','".$this->post['tujuan']."',NOW(),'".$_SERVER['REMOTE_ADDR']."')");
            if ($this->db_affected()>0) {
                $this->flash("Tambah data sukses");
                $this->go_to($this->basepath."persuratan/suratkeluar");
            } else {
                $this->error(mysql_error());
            }
        }  
            else  
        {
            $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
 
            $this->v .= "
            <div class='btn-group btn-breadcrumb'>
                <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                <a href='".$this->basepath."persuratan/suratkeluar/' class='btn btn-default'><div>Daftar Suratkeluar</div></a>
                <a href='#' class='btn btn-default'><div>Tambah Suratkeluar</div></a>
            </div>

            <div class='panel panel-default'>
                <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Tambah Data Suratkeluar</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Status alur</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_flowstat_id' id='input1'>
                                    <option value=''>-- Pilih status alur--</option>";
                while (list($key,$val) = each($arr_ms_flowstat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_flowstat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tipesurat_id' id='input2'>
                                    <option value=''>-- Pilih tipe surat--</option>";
                while (list($key,$val) = each($arr_ms_tipesurat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Nomor surat</label>
                            <div class='col-sm-7'>
                                <input name='nomorsurat' placeholder='Nomor surat' value='".$row['nomorsurat']."' title='' id='input3' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Tanggal</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_date' data-date='".$row['tglsurat']."' data-date-format='dd-mm-yyyy' data-link-field='input4' data-link-format='dd-mm-yyyy'>
                                    <input size='16' type='text' value='".$row['tglsurat']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='tglsurat' placeholder='dd-mm-yyyy' value='".$row['tglsurat']."' title='input dengan format (dd-mm-yyyy)' id='input4' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>Perihal</label>
                            <div class='col-sm-7'>
                                <textarea name='perihal' placeholder='Perihal' id='input5' class='form-control'>".$row['perihal']."</textarea>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Tujuan</label>
                            <div class='col-sm-7'>
                                <input name='tujuan' placeholder='Tujuan' value='".$row['tujuan']."' title='' id='input6' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <a href='".$this->basepath."persuratan/suratkeluar/' class='btn btn-default'>Batal</a>&nbsp;
                      <button type='submit' class='btn btn-primary'>Simpan</button>
                    </div>
                </form>
            </div>
            ";
        }
    }

    /**
     *
     * Edit persuratan_suratkeluar
     *
     **/
    function action_persuratan_suratkeluaredit($id) {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("UPDATE persuratan_suratkeluar SET ms_flowstat_id = '".$this->post['ms_flowstat_id']."', ms_tipesurat_id = '".$this->post['ms_tipesurat_id']."', nomorsurat = '".$this->post['nomorsurat']."', tglsurat = STR_TO_DATE('".$this->post['tglsurat']."', '%d-%m-%Y'), perihal = '".$this->post['perihal']."', tujuan = '".$this->post['tujuan']."', modified=NOW() WHERE id='".$this->post['id']."' {$this->groupfield}");

            if ($this->db_affected()>0) {
                $this->flash("Update data sukses");
                $this->go_to($this->basepath."persuratan/suratkeluar");
            } else {
                $this->error(mysql_error());
            }
        } 
            else 
        {
            $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
 
            $res = $this->db_query("SELECT * , DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratkeluar WHERE id='{$id}' {$this->groupfield}");
            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);
                $this->v .= "
                <div class='btn-group btn-breadcrumb'>
                    <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
                    <a href='#' class='btn btn-default'><div>Persuratan</div></a>
                    <a href='".$this->basepath."persuratan/suratkeluar/' class='btn btn-default'><div>Daftar Suratkeluar</div></a>
                    <a href='#' class='btn btn-default'><div>Ubah Data Suratkeluar</div></a>
                </div>

                <div class='panel panel-default'>
                    <div class='panel-heading'><span class='glyphicon glyphicon-pencil'></span> Ubah Data Suratkeluar</div>
                    <form class='form-horizontal' role='form' method='POST'>
                        <div class='panel-body'>";
                $this->v .= "
                <div class='row'>
                    <div class='col-md-6'>
                                    <div class='form-group'>
                            <label for='input1' class='col-sm-5 control-label'>Status alur</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_flowstat_id' id='input1'>
                                    <option value=''>-- Pilih status alur--</option>";
                while (list($key,$val) = each($arr_ms_flowstat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_flowstat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input2' class='col-sm-5 control-label'>Tipe surat</label>
                            <div class='col-sm-7'>
                                <select class='form-control' name='ms_tipesurat_id' id='input2'>
                                    <option value=''>-- Pilih tipe surat--</option>";
                while (list($key,$val) = each($arr_ms_tipesurat)) {
                    $this->v .= "<option value='".$key."'".($key==$row['ms_tipesurat_id']?" SELECTED":"").">".$val."</option>";
                }
                $this->v .= "
                                </select>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input3' class='col-sm-5 control-label'>Nomor surat</label>
                            <div class='col-sm-7'>
                                <input name='nomorsurat' placeholder='Nomor surat' value='".$row['nomorsurat']."' title='' id='input3' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                    <div class='col-md-6'>
                        <div class='form-group'>
                            <label for='input4' class='col-sm-5 control-label'>Tanggal</label>
                            <div class='col-sm-7'>
                                <div class='controls input-append date form_date' data-date='".$row['tglsurat']."' data-date-format='dd-mm-yyyy' data-link-field='input4' data-link-format='dd-mm-yyyy'>
                                    <input size='16' type='text' value='".$row['tglsurat']."' readonly class='form-control'><span class='add-on'><i class='icon-remove'></i></span><span class='add-on'><i class='icon-th'></i></span>
                                </div>
                                <input name='tglsurat' placeholder='dd-mm-yyyy' value='".$row['tglsurat']."' title='input dengan format (dd-mm-yyyy)' id='input4' type='hidden' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input5' class='col-sm-5 control-label'>Perihal</label>
                            <div class='col-sm-7'>
                                <textarea name='perihal' placeholder='Perihal' id='input5' class='form-control'>".$row['perihal']."</textarea>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input6' class='col-sm-5 control-label'>Tujuan</label>
                            <div class='col-sm-7'>
                                <input name='tujuan' placeholder='Tujuan' value='".$row['tujuan']."' title='' id='input6' type='text' class='form-control'>
                            </div>
                        </div>
                    </div>
                </div> <!-- end of row-fluid -->
                ";
                    $this->v .= "</div>
                        <div class='panel-footer text-right'>
                          <a href='".$this->basepath."persuratan/suratkeluar/' class='btn btn-default'>Batal</a>&nbsp;
                          <button type='submit' class='btn btn-primary'>Simpan</button>
                        </div>
                        <input name='id' id='id' value='".$row['id']."' type='hidden'>
                    </form>
                </div>
                ";
            } else {
                $this->error("Maaf data tidak ditemukan.");
            }
        }
    }

    /**
     *
     * Delete persuratan_suratkeluar
     *
     **/
    function action_persuratan_suratkeluardelete($id) {
        $res = $this->db_query("DELETE FROM persuratan_suratkeluar WHERE id='{$id}' {$this->groupfield}");

        if ($this->db_affected()>0) {
            $this->flash("Hapus data sukses");
            $this->go_to($this->basepath."persuratan/suratkeluar");
        } else {
            $this->error(mysql_error());
        }
    }

    /**
     *
     * Export persuratan_suratkeluar to HTML
     *
     **/
    function action_persuratan_suratkeluarhtml()
    {
        $this->blankpage = true;

        $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['name']; }
        $this->v .= "
                <style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
                <h1>Daftar Suratkeluar</h1>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                                <th>Status alur</th>
                                <th>Tipe surat</th>
                                <th>Nomor surat</th>
                                <th>Tanggal</th>
                                <th>Perihal</th>
                                <th>Tujuan</th>
                            <th nowrap></th>
                        </tr>
                    </thead>
                    <tbody>
        ";

        $this->db_query($this->session['last_sql'], array());
        $no = 1;
        while ($row = $this->db_fetch()) {
            $this->v .= "
                        <tr>
                            <td>".$no."</td>
                                <td>".$arr_ms_flowstat[$row[ms_flowstat_id]]."</td>
                                <td>".$arr_ms_tipesurat[$row[ms_tipesurat_id]]."</td>
                                <td>".$row[nomorsurat]."</td>
                                <td>".$row[tglsurat]."</td>
                                <td>".$row[perihal]."</td>
                                <td>".$row[tujuan]."</td>
                        </tr>";
            $no++;
        }
        
        $this->v .= "
                    </tbody>
                </table>";
    }

    /**
     *
     * Export persuratan_suratkeluar view to HTML
     *
     **/
    function action_persuratan_suratkeluarviewhtml($id)
    {
        $this->blankpage = true;
        $res = $this->db_query("SELECT * , DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratkeluar WHERE id='{$id}' {$this->groupfield}");
        if ($this->db_numrows()>0) {
            $row = $this->db_fetch($res);
            $this->v .= "<style>* {font-family:verdana;} th,td {border:1px solid #000; font-size:8px;}</style>
            <h1>Data Suratkeluar</h1>
            <table>
                <tr><td>Status alur</td><td>".$arr_ms_flowstat[$row['ms_flowstat_id']]."</td></tr>
                <tr><td>Tipe surat</td><td>".$arr_ms_tipesurat[$row['ms_tipesurat_id']]."</td></tr>
                <tr><td>Nomor surat</td><td>".$row['nomorsurat']."</td></tr>
                <tr><td>Tanggal</td><td>".$row['tglsurat']."</td></tr>
                <tr><td>Perihal</td><td>".$row['perihal']."</td></tr>
                <tr><td>Tujuan</td><td>".$row['tujuan']."</td></tr>
            </table>";
        }
    }


    /**
     *
     * User Roles
     *
     **/
    function user_role($modulename, $action)
    {
        if ($action=='page') $action = 'browse';
        $this->db_query("SELECT * FROM role 
            JOIN users_roles ON (role.id = users_roles.role_id) 
            JOIN users_roleaccess ON (role.id = users_roleaccess.role_id)
            WHERE users_roleaccess.modulename = '{$modulename}' 
            AND users_roleaccess.{$action}=1 
            AND users_roles.user_id = ".$_SESSION['user']['id'], array(''));
        if ($this->db_numrows()==0) {
            $this->error("Maaf anda tidak memiliki akses ke halaman ini.");
            return false;
        } else {
            // Check if group field
            $row = $this->db_fetch($res);
            $groupfield = $row['groupfield'];
            if ($groupfield != '') {
                $res = $this->db_query("SELECT {$groupfield}_id FROM role_{$groupfield} WHERE user_id = ".$_SESSION['user']['id'], array(''));
                $row = $this->db_fetch($res);
                $groupfield_id = $row[$groupfield.'_id'];
                if ( $groupfield_id != 'ALL' ) {
                    $this->groupfield = " AND {$groupfield}_id IN ({$groupfield_id}) ";
                } else {
                    $this->groupfield = '';
                }
            } else {
                $this->groupfield = '';
            }

            return true;
        }
    }

    /**
     *
     * Login
     *
     **/
    function action_login_()
    {
        if ($_SERVER['REQUEST_METHOD']=="POST")
        {
            $res = $this->db_query("SELECT * FROM user_user
                WHERE name='".$this->post['name']."' AND password=MD5('".$this->post['pass']."')");

            if ($this->db_numrows()>0) {
                $row = $this->db_fetch($res);

                $_SESSION['user']['id']     = $row['id'];
                $_SESSION['user']['nama']   = $row['namalengkap'];
                $_SESSION['user']['nip']    = $row['nip'];
                $_SESSION['user']['jabatan']= $row['jabatan'];

                $this->flash("Selamat datang di aplikasi");
                $this->go_to($this->basepath.'persuratan/suratmasuk/');
            } else {
                $this->flash("Maaf, login anda salah.<br><b>coba ulangi kembali</b>");
                $this->go_to($this->basepath.'login/');
            }
        } 
            else 
        {
            $this->v .= "
            <br><br><br><center>
            <div class='panel panel-default' style='width:50%;'>
                <div class='panel-heading text-left'><span class='glyphicon glyphicon-pencil'></span> Login</div>
                <form class='form-horizontal' role='form' method='POST'>
                    <div class='panel-body'>";
                $this->v .= "
                        <div class='form-group'>
                            <label for='input01' class='col-sm-2 control-label'>User Name</label>
                            <div class='col-sm-10'>
                                <input name='name' placeholder='nama user' id='input01' value='' type='text' class='form-control'>
                            </div>
                        </div>
                        <div class='form-group'>
                            <label for='input02' class='col-sm-2 control-label'>Password</label>
                            <div class='col-sm-10'>
                                <input name='pass' id='input02' value='' type='password' class='form-control'>
                            </div>
                        </div>";
                $this->v .= "
                    </div>
                    <div class='panel-footer text-right'>
                      <button type='submit' class='btn btn-primary'>LOGIN</button>
                    </div>
                </form>
            </div>
            </center>
            ";
        }
    }

    /**
     *
     * Logout
     *
     **/
    function action_logout_()
    {
        session_unset();
        session_destroy();
        $_SESSION = array();
        $this->flash("Anda berhasil logout.<br><b>Terima kasih.</b>");
        $this->go_to($this->basepath.'login/');
    }

    /**
     *
     * Page header
     *
     **/
    function header() {
        echo "
<!DOCTYPE html>
<html lang='en'>

<head>

    <meta charset='utf-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <meta name='description' content=''>
    <meta name='author' content=''>

    <title>Aplikasi Surat Sekretariat Wakil Ketua MA Bidang Non Yudisial</title>

    <!-- Bootstrap Core CSS -->
    <link href='".$this->basepath."dist/css/bootstrap.min.css' rel='stylesheet'>

    <!-- Custom CSS -->
    <link href='".$this->basepath."dist/css/bootstrap.min.css' rel='stylesheet' media='screen'>
    <link href='".$this->basepath."dist/css/bootstrap-datetimepicker.min.css' rel='stylesheet' media='screen'>
    <link href='".$this->basepath."dist/css/sb-admin.css' rel='stylesheet'>
    <link href='".$this->basepath."dist/css/apps.css' rel='stylesheet'>
    <!-- <link href='css/simple-sidebar.css' rel='stylesheet'> -->

    <!-- Morris Charts CSS -->
    <link href='".$this->basepath."dist/css/plugins/morris.css' rel='stylesheet'>

    <!-- Custom Fonts -->
    <link href='".$this->basepath."dist/font-awesome-4.1.0/css/font-awesome.min.css' rel='stylesheet' type='text/css'>

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src='https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js'></script>
        <script src='https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js'></script>
    <![endif]-->
</head>
<body>

    <div id='wrapper'>

        <!-- Navigation -->
        <nav class='navbar navbar-fixed-top' role='navigation'>
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class='navbar-header'>
                <button type='button' class='navbar-toggle' data-toggle='collapse' data-target='.navbar-ex1-collapse'>
                    <span class='sr-only'>Toggle navigation</span>
                    <span class='icon-bar'></span>
                    <span class='icon-bar'></span>
                    <span class='icon-bar'></span>
                </button>
                <a class='navbar-brand' href='#' style='color:#fff;font-weight:bold;'><img alt='Brand' src='".$this->basepath."dist/img/logo.png' style='width:28px; height:43px; margin:-15px -10px;'> &nbsp; &nbsp;Aplikasi Surat Sekretariat Wakil Ketua MA Bidang Non Yudisial</a>
            </div>
            ".( !empty($_SESSION['user']['id']) ? "
            <ul class='nav navbar-left top-nav'>
                <li class='dropdown'>
                    <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='fa fa-user'></i> Daftar Surat <b class='caret'></b></a>
                    <ul class='dropdown-menu'>
                        <li><a href='".$this->basepath."persuratan/suratmasuk1/'><span class='fa fa-fw fa-envelope'></span> Daftar surat masuk</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk2/'><span class='fa fa-fw fa-calendar-o'></span> Daftar memo Kepala Biro</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk3/'><span class='fa fa-fw fa-group'></span> Daftar surat keputusan</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk4/'><span class='fa fa-fw fa-paper-plane'></span> Daftar surat keluar</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk5/'><span class='fa fa-fw fa-child'></span> Daftar surat tugas</a></li>
                    </ul>
                </li>
                <li class='dropdown'>
                    <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='fa fa-user'></i> Entri Surat <b class='caret'></b></a>
                    <ul class='dropdown-menu'>
                        <li><a href='".$this->basepath."persuratan/suratmasuk/add/1/'><span class='fa fa-fw fa-envelope'></span> Entri surat masuk</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk/add/2/'><span class='fa fa-fw fa-calendar-o'></span> Entri memo Kepala Biro</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk/add/3/'><span class='fa fa-fw fa-group'></span> Entri surat keputusan</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk/add/4/'><span class='fa fa-fw fa-paper-plane'></span> Entri surat keluar</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk/add/5/'><span class='fa fa-fw fa-child'></span> Entri surat tugas</a></li>
                    </ul>
                </li>
                <li class='dropdown'>
                    <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='fa fa-print'></i> Cetak <b class='caret'></b></a>
                    <ul class='dropdown-menu'>
                        <li><a href='".$this->basepath."persuratan/suratmasukld/'><span class='fa fa-fw fa-print'></span> Cetak lembar disposisi</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasukle/'><span class='fa fa-fw fa-print'></span> Cetak lembar ekspedisi</a></li>
                    </ul>
                </li>
            </ul>
            <ul class='nav navbar-right top-nav'>
                <li class='dropdown'>
                    <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='fa fa-user'></i> Master Data <b class='caret'></b></a>
                    <ul class='dropdown-menu'>
                        <li><a href='".$this->basepath."persuratan/ms_tipesurat'><i class='fa fa-fw fa-dashboard'></i> Tipe surat</a></li>
                        <li><a href='".$this->basepath."persuratan/ms_sifatsurat'><i class='fa fa-fw fa-dashboard'></i> Sifat surat</a></li>
                        <li><a href='".$this->basepath."persuratan/ms_tujuanakhir'><i class='fa fa-fw fa-dashboard'></i> Tujuan akhir</a></li>
                        <li><a href='".$this->basepath."persuratan/ms_flowstat'><i class='fa fa-fw fa-dashboard'></i> Flow stat</a></li>
                        <li><a href='".$this->basepath."persuratan/nomorsurat'><i class='fa fa-fw fa-dashboard'></i> Nomor surat</a></li>
                        <li><a href='".$this->basepath."persuratan/suratmasuk'><i class='fa fa-fw fa-dashboard'></i> Surat masuk</a></li>
                        <li><a href='".$this->basepath."persuratan/suratkeluar'><i class='fa fa-fw fa-dashboard'></i> Surat keluar</a></li>
                    </ul>
                </li>
                <li class='dropdown'>
                    <a href='#' class='dropdown-toggle' data-toggle='dropdown'><i class='fa fa-user'></i> ".$_SESSION['user']['nama']." <b class='caret'></b></a>
                    <ul class='dropdown-menu'>
                        <li>
                            <a href='".$this->basepath."profile'><i class='fa fa-fw fa-user'></i> Profile</a>
                        </li>
                        <li>
                            <a href='#'><i class='fa fa-fw fa-envelope'></i> Inbox</a>
                        </li>
                        <li>
                            <a href='".$this->basepath."setting/about'><i class='fa fa-fw fa-gear'></i> Settings</a>
                        </li>
                        <li class='divider'></li>
                        <li>
                            <a href='#' onclick='location.href=\"".$this->basepath."logout\"'><i class='fa fa-fw fa-power-off'></i> Log Out</a>
                        </li>
                    </ul>
                </li>
            </ul>" 
            : '' )."
            <!-- /.navbar-collapse -->
        </nav>
        <div id='page-wrapper'>
            <div class='container-fluid'>
        ";
    }

    /**
     *
     * Page footer
     *
     **/
    function footer() {
        echo "
            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- /#page-wrapper -->

    </div>
    <!-- /#wrapper -->

    <script src='".$this->basepath."dist/js/jquery-1.11.0.js'></script>
    <script src='".$this->basepath."dist/js/bootstrap.min.js'></script>
    <script src='".$this->basepath."dist/js/bootstrap-datetimepicker.min.js'></script>
    <script src='".$this->basepath."dist/js/bootstrap-filestyle.js'> </script>
    <script type='text/javascript'>
        $('.form_datetime').datetimepicker({weekStart: 1, todayBtn:  1, autoclose: 1, todayHighlight: 1, startView: 2, forceParse: 0, showMeridian: 1});
        $('.form_date').datetimepicker({language:  'fr', weekStart: 1, todayBtn:  1, autoclose: 1, todayHighlight: 1, startView: 2, minView: 2, forceParse: 0 });
        $('.form_time').datetimepicker({language:  'fr', weekStart: 1, todayBtn:  1, autoclose: 1, todayHighlight: 1, startView: 1, minView: 0, maxView: 1, forceParse: 0 });
        $(':file').filestyle({buttonText: ' Pilih file'});
    </script>
    <!-- create by agus.sudarmanto@gmail.com  - rev Thursday 30th of October 2014 02:10:36 AM -->
  </body>
</html>
        ";
    }
}
?>