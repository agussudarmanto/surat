<?php
class base {
    function action_persuratan_suratmasuk1() {
        $this->post['filter']['ms_tipesurat_id'] = 1;
        $this->action_persuratan_suratmasuk();
    }
    
    function action_persuratan_suratmasuk2() {
        $this->post['filter']['ms_tipesurat_id'] = 2;
        $this->action_persuratan_suratmasuk();
    }
    
    function action_persuratan_suratmasuk3() {
        $this->post['filter']['ms_tipesurat_id'] = 3;
        $this->action_persuratan_suratmasuk();
    }
    
    function action_persuratan_suratmasuk4() {
        $this->post['filter']['ms_tipesurat_id'] = 4;
        $this->action_persuratan_suratmasuk();
    }

    function action_persuratan_suratmasuk5() {
        $this->post['filter']['ms_tipesurat_id'] = 5;
        $this->action_persuratan_suratmasuk();
    }

    /**
     *
     * View detail of persuratan_suratmasuk
     *
     **/
    function action_persuratan_suratmasukpdf($id)
    {
        $this->blankpage = true;

        $this->db_query("SELECT * FROM persuratan_ms_flowstat");
        while ( $row = $this->db_fetch() ) { $arr_ms_flowstat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_jenissuratmasuk");
        while ( $row = $this->db_fetch() ) { $arr_ms_jenissuratmasuk[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_asalsurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_asalsurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_sifatsurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_sifatsurat[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_jenispengaduan");
        while ( $row = $this->db_fetch() ) { $arr_ms_jenispengaduan[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_status");
        while ( $row = $this->db_fetch() ) { $arr_ms_status[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_pengurus");
        while ( $row = $this->db_fetch() ) { $arr_ms_pengurus[$row['id']] = $row['name']; }
        $this->db_query("SELECT * FROM persuratan_ms_statusdisposisi");
        while ( $row = $this->db_fetch() ) { $arr_ms_statusdisposisi[$row['id']] = $row['name']; }
 
        $res = $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglsuratintern, '%d-%m-%Y') AS tglsuratintern, DATE_FORMAT(tglkma, '%d-%m-%Y') AS tglkma, DATE_FORMAT(tglsuratpengirim, '%d-%m-%Y') AS tglsuratpengirim, DATE_FORMAT(tglpembukuan, '%d-%m-%Y') AS tglpembukuan, DATE_FORMAT(tglmasuktuadawas, '%d-%m-%Y') AS tglmasuktuadawas, DATE_FORMAT(tglkeluar, '%d-%m-%Y') AS tglkeluar FROM persuratan_suratmasuk 
            WHERE id='{$id}'");
        if ($this->db_numrows()>0) {
            //==============
            // Start create PDF
            //==============
            require('fpdf/fpdf.php');
            $pdf = new FPDF('P', 'mm', array(215,330));
            $pdf->SetAuthor('Agus Sudarmanto, S.Kom.');

            while ($row = $this->db_fetch()) {

                $pdf->AddPage();
                $pdf->SetFont('Arial','B',16);

                $cellBox = 195;

                $pdf->Rect(10, 7, $cellBox , 25);
                $pdf->Rect(10, 38, $cellBox , 24);
                $pdf->Rect(150, 38, 55 , 24);
                $pdf->Rect(10, 62, $cellBox , 50);
                $pdf->Rect(10, 118, $cellBox , 133);
                $pdf->Rect(150, 118, 55 , 133);

                $pdf->Cell($cellBox,7,'SEKRETARIAT WAKIL KETUA MA BIDANG NON YUDISIAL',0,1,'C');
                $pdf->Cell($cellBox,7,'MAHKAMAH AGUNG REPUBLIK INDONESIA',0,1,'C');
                $pdf->SetFont('Arial','B',12);

                $pdf->Cell($cellBox,7,'Lembar Disposisi',0,1,'C');
                $pdf->Image('pdf/logo.png',23,10,16);

                $pdf->SetFont('Arial','',10);
                $arr = array(
                        array('Nomor Agenda',   $row[noagenda]),
                        array('Tanggal Agenda', $row[tglagenda]),
                        array('Agno TU',        $row[nosuratintern]),
                        array('Tanggal TU',     $row[tglsuratintern]),
                        array('', ''),
                        array('Nomor Surat',    $row[nosuratpengirim]),
                        array('Tanggal',        $row[tglsuratpengirim]),
                        array('Pengirim',       $row[namapengirim]),
                        array('Perihal',        $row[perihal])
                    );

                $pdf->SetY(40);

                for ($i=0, $c=sizeof($arr); $i<$c; $i++) {
                    $pdf->SetX(13);
                    $pdf->Cell(31, 5, $arr[$i][0]);
                    $pdf->Cell(5,5,( !in_array($i, array(4,9)) ? ':' : '') );
                    $pdf->MultiCell( ($i < 5 ? 100 : 158), 5, $arr[$i][1],0,1);
                }

                $pdf->SetY(45);

                $arr = array(
                        array('Jenis Surat', $arr_ms_sifatsurat[$row['ms_sifatsurat_id']])
                    );


                for ($i=0, $c=sizeof($arr); $i<$c; $i++) {
                    $pdf->SetX(155);
                    $pdf->Cell(20, 5, $arr[$i][0]);
                    $pdf->Cell(5,5,':');
                    if ($i==0) $pdf->SetFont('','B');
                    $pdf->MultiCell(70, 5, $arr[$i][1],0,1);
                    if ($i==0) $pdf->SetFont('','');
                }

                $arr = array(
                        array('Diteruskan kepada :'),
                        array('KMA RI'),
                        array('WKMA RI Bid. Yud'),
                        array('WKMA RI Bid. Non Yud'),
                        array('Para Tuaka'),
                        array('Kabawas'),
                        array('Lainnya'),
                    );

                $pdf->SetY(120);

                for ($i=0, $c=sizeof($arr); $i<$c; $i++) {
                    $pdf->SetX(($i==0?155:163));
                    $pdf->Cell(31, 7, $arr[$i][0], 0, 1);
                }

                $pdf->SetFont('Arial','',8);
                $pdf->SetY(251);

                for ($i=0; $i<6; $i++) {
                    $pdf->Rect(157, 128+($i*7), 4 , 4);
                }

                $pdf->SetY(150);
                $pdf->SetX(130);

            }
                
            $pdf->Output();
        }
    }

    /**
     *
     * View detail of persuratan_suratmasuk
     *
     **/
    function action_persuratan_suratmasukcetakld($id)
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
 
        $res = $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglsuratintern, '%d-%m-%Y') AS tglsuratintern, DATE_FORMAT(tglkma, '%d-%m-%Y') AS tglkma, DATE_FORMAT(tglsuratpengirim, '%d-%m-%Y') AS tglsuratpengirim, DATE_FORMAT(tglpembukuan, '%d-%m-%Y') AS tglpembukuan, DATE_FORMAT(tglmasuktuadawas, '%d-%m-%Y') AS tglmasuktuadawas, DATE_FORMAT(tglkeluar, '%d-%m-%Y') AS tglkeluar FROM persuratan_suratmasuk 
            WHERE ms_tujuanakhir_id='{$id}' AND ms_flowstat_id = 101
                {$this->groupfield} ORDER BY tglagenda DESC");
        
        $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglsurat, '%d-%m-%Y') AS tglsurat FROM persuratan_suratmasuk WHERE ms_tujuanakhir_id='{$id}' AND ms_flowstat_id = 101 {$this->groupfield} ORDER BY tglagenda DESC, id DESC");
            
        if ($this->db_numrows()>0) {
            //==============
            // Start create PDF
            //==============
            require('fpdf/fpdf.php');
            $pdf = new FPDF('P', 'mm', array(215,330));
            $pdf->SetAuthor('Agus Sudarmanto, S.Kom.');

            while ($row = $this->db_fetch()) {

                $pdf->AddPage();
                $pdf->SetFont('Arial','B',16);

                $cellBox = 195;

                $pdf->Rect(10, 7, $cellBox , 25);
                $pdf->Rect(10, 38, $cellBox , 24);
                $pdf->Rect(150, 38, 55 , 24);
                $pdf->Rect(10, 62, $cellBox , 50);
                $pdf->Rect(10, 118, $cellBox , 133);
                $pdf->Rect(150, 118, 55 , 133);

                $pdf->Cell($cellBox,7,'MAHKAMAH AGUNG REPUBLIK INDONESIA',0,1,'C');
                $pdf->Cell($cellBox,7,'SEKRETARIAT WAKIL KETUA MA BIDANG NON YUDISIAL',0,1,'C');
                $pdf->SetFont('Arial','B',12);
                $pdf->Cell($cellBox,7,'Lembar Disposisi',0,1,'C');
                $pdf->Image('pdf/logo.png',23,10,16);

                $pdf->SetFont('Arial','',10);
                $arr = array(
                        array('Nomor Agenda',   $row[noagenda]),
                        array('Tanggal Agenda', $row[tglagenda]),
                        array('Agno TU',        $row[nosuratintern]),
                        array('Tanggal TU',     $row[tglsuratintern]),
                        array('', ''),
                        array('Nomor Surat',    $row[nosuratpengirim]),
                        array('Tanggal',        $row[tglsuratpengirim]),
                        array('Pengirim',       $row[namapengirim]),
                        array('Perihal',        $row[perihal])
                    );

                $pdf->SetY(40);

                for ($i=0, $c=sizeof($arr); $i<$c; $i++) {
                    $pdf->SetX(13);
                    $pdf->Cell(31, 5, $arr[$i][0]);
                    $pdf->Cell(5,5,( !in_array($i, array(4,9)) ? ':' : '') );
                    $pdf->MultiCell( ($i < 5 ? 100 : 158), 5, $arr[$i][1],0,1);
                }

                $pdf->SetY(45);

                $arr = array(
                        array('Jenis Surat', $arr_ms_sifatsurat[$row['ms_sifatsurat_id']])
                    );


                for ($i=0, $c=sizeof($arr); $i<$c; $i++) {
                    $pdf->SetX(155);
                    $pdf->Cell(20, 5, $arr[$i][0]);
                    $pdf->Cell(5,5,':');
                    if ($i==0) $pdf->SetFont('','B');
                    $pdf->MultiCell(70, 5, $arr[$i][1],0,1);
                    if ($i==0) $pdf->SetFont('','');
                }

                $arr = array(
                        array('Diteruskan kepada :'),
                        array('KMA RI'),
                        array('WKMA RI Bid. Yud'),
                        array('WKMA RI Bid. Non Yud'),
                        array('Para Tuaka'),
                        array('Kabawas'),
                        array('Lainnya'),
                    );

                $pdf->SetY(120);

                for ($i=0, $c=sizeof($arr); $i<$c; $i++) {
                    $pdf->SetX(($i==0?155:163));
                    $pdf->Cell(31, 7, $arr[$i][0], 0, 1);
                }

                $pdf->SetFont('Arial','',8);
                $pdf->SetY(251);

                for ($i=0; $i<6; $i++) {
                    $pdf->Rect(157, 128+($i*7), 4 , 4);
                }

                $pdf->SetY(150);
                $pdf->SetX(130);

            }
                
            $pdf->Output();

            $res = $this->db_query("UPDATE persuratan_suratmasuk 
                SET ms_flowstat_id = 102
                WHERE ms_tujuanakhir_id='{$id}' AND ms_flowstat_id = 101");
        }
    }

    function get_next_id($tipe_surat_id)
    {
        $nextid = 1;

        $this->db_query("SELECT nextid FROM persuratan_nomorsurat WHERE year='".date('Y')."' AND ms_tipesurat_id='{$tipe_surat_id}'");
        if ($this->db_numrows() > 0) {
            $row = $this->db_fetch();
            $nextid = $row['nextid'];
        } else {
            $this->db_query("INSERT INTO persuratan_nomorsurat (nextid, `year`, ms_tipesurat_id)  VALUES (2, '".date('Y')."', '{$tipe_surat_id}')");
        }


        return $nextid;
    }

    function update_next_id($tipe_surat_id, $year)
    {
        $this->db_query("UPDATE persuratan_nomorsurat SET nextid=nextid+1 WHERE year='{$year}' AND ms_tipesurat_id='{$tipe_surat_id}'");
        $this->db_fetch();

        return true;
    }

    function suratmasukadd()
    {
        $arr_bulan = array(1=>'I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII');

        $this->db_query("SELECT * FROM persuratan_ms_tipesurat");
        while ( $row = $this->db_fetch() ) { $arr_ms_tipesurat[$row['id']] = $row['kode']; }

        $month = date("m",strtotime($this->post['tglagenda']));

        $this->post['noagenda']  = $this->get_next_id( $this->post['ms_tipesurat_id'] ).$arr_ms_tipesurat[$this->post['ms_tipesurat_id']].$arr_bulan[$month]."/".date('Y');
        $this->post['ms_flowstat_id'] = 101;

        $_SESSION['last_noagenda']      = "<b>".$this->post['noagenda']."</b> tanggal <b>".$this->post['tglagenda']."</b>";
        $_SESSION['last_nosurat'] = "<b>".$this->post['nosurat']."</b> tanggal <b>".$this->post['tglsurat']."</b>";

        $res = $this->db_query("INSERT INTO persuratan_suratmasuk (ms_flowstat_id,ms_tipesurat_id,ms_sifatsurat_id,ms_tujuanakhir_id,noagenda,tglagenda,nosurat,tglsurat,asalsurat,perihal,namapengirim,tujuansurat,disposisipimpinan,lampiran,created,ipaddress,ms_arsip_id) 
                VALUES ('".$this->post['ms_flowstat_id']."','".$this->post['ms_tipesurat_id']."','".$this->post['ms_sifatsurat_id']."','".$this->post['ms_tujuanakhir_id']."','".$this->post['noagenda']."',STR_TO_DATE('".$this->post['tglagenda']."', '%d-%m-%Y'),'".$this->post['nosurat']."',STR_TO_DATE('".$this->post['tglsurat']."', '%d-%m-%Y'),'".$this->post['asalsurat']."','".$this->post['perihal']."','".$this->post['namapengirim']."','".$this->post['tujuansurat']."','".$this->post['disposisipimpinan']."','".$this->post['lampiran']."',NOW(),'".$_SERVER['REMOTE_ADDR']."','".$this->post['ms_arsip_id']."')");

        if ($this->db_affected()>0) {
            $this->update_next_id( $this->post['ms_tipesurat_id'] , date('Y'));
            $this->flash("Tambah data sukses");
            $this->go_to($this->basepath."persuratan/suratmasuk/add/" . $this->post['ms_tipesurat_id']);
        } else {
            $this->flash("Tambah data gagal");
            $this->go_to($this->basepath."persuratan/suratmasukadd");
        }
    }


    /**
     *
     * daftar LEMBAR DISTRIBUSI
     *
     **/
    function action_persuratan_suratmasukld()
    {
        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-default'><div>Daftar Suratmasuk</div></a>
            <a href='#' class='btn btn-default'><div><b>Cetak lembar disposisi</b></div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Cetak Lembar Disposisi Surat Masuk</div>
                  <div class='col-md-6 text-right'>
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
                                <th>Tujuan</th>
                                <th>Jumlah surat</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT persuratan_ms_tujuanakhir.id, persuratan_ms_tujuanakhir.name, COUNT(*) as jml 
                                FROM persuratan_suratmasuk JOIN persuratan_ms_tujuanakhir ON (persuratan_suratmasuk.ms_tujuanakhir_id = persuratan_ms_tujuanakhir.id)
                                WHERE persuratan_suratmasuk.ms_flowstat_id = 101
                                GROUP BY persuratan_suratmasuk.ms_tujuanakhir_id", array());
            $no = 1;
            if ($this->db_numrows() > 0) {
                while ($row = $this->db_fetch()) {
                    if (!empty($row[name])) {
                        $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[name]."</td>
                                <td>".$row[jml]."</td>
                                <td nowrap>
                                    <a href='#' onClick=\"print('".$this->basepath."persuratan/suratmasuk/cetakld/".$row["id"]."/'); return false;\" title='Cetak lembar disposisi' class='btn btn-default btn-xs glyphicon glyphicon-print'></a>
                                </td>
                            </tr>";
                        $no++;
                    } else {
                        $this->v .= "<tr><td colspan=10 height='100px;'>Tidak ada data</td></tr>";
                    }
                }
            }
            
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
            </ul>
        </div>
        <script>
        var print = function(url) {
            window.open(url, 'ld');
            setTimeout(function(){window.location.reload()},3000);
            return false;
        }
        </script>
        ";
    }


    /**
     *
     * daftar LEMBAR EKSPEDISI
     *
     **/
    function action_persuratan_suratmasukle()
    {
        $this->v .= "
        <div class='btn-group btn-breadcrumb'>
            <a href='#' class='btn btn-default'><i class='fa fa-home'></i></a>
            <a href='#' class='btn btn-default'><div>Persuratan</div></a>
            <a href='".$this->basepath."persuratan/suratmasuk/' class='btn btn-default'><div>Daftar Suratmasuk</div></a>
            <a href='#' class='btn btn-default'><div><b>Cetak lembar ekspedisi</b></div></a>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading container-fluid'>
                <div class='row'>
                  <div class='col-md-6'>
                    <span class='glyphicon glyphicon-calendar'></span> Cetak Lembar Ekspedisi Surat Masuk</div>
                  <div class='col-md-6 text-right'>
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
                                <th>Tujuan</th>
                                <th>Jumlah surat</th>
                                <th nowrap></th>
                            </tr>
                        </thead>
                        <tbody>
            ";

            $this->db_query("SELECT persuratan_ms_tujuanakhir.id, persuratan_ms_tujuanakhir.name, COUNT(*) as jml 
                                FROM persuratan_suratmasuk JOIN persuratan_ms_tujuanakhir ON (persuratan_suratmasuk.ms_tujuanakhir_id = persuratan_ms_tujuanakhir.id)
                                WHERE persuratan_suratmasuk.ms_flowstat_id = 102
                                GROUP BY persuratan_suratmasuk.ms_tujuanakhir_id", array());
            $no = 1;
            if ($this->db_numrows() > 0) {
                while ($row = $this->db_fetch()) {
                    if (!empty($row[name])) {
                        $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[name]."</td>
                                <td>".$row[jml]."</td>
                                <td nowrap>
                                    <a href='#' onClick=\"print('".$this->basepath."persuratan/suratmasuk/cetakle/".$row["id"]."/'); return false;\" title='Cetak lembar ekspedisi' class='btn btn-default btn-xs glyphicon glyphicon-print'></a>
                                </td>
                            </tr>";
                        $no++;
                    } else {
                        $this->v .= "<tr><td colspan=10 height='100px;'>Tidak ada data</td></tr>";
                    }
                }
            }
            $this->v .= "
                        </tbody>
                    </table>
                </li>";
                
                $this->v .= "
            </ul>
        </div>
        <script>
        var print = function(url) {
            window.open(url, 'ld');
            setTimeout(function(){window.location.reload()},3000);
            return false;
        }
        </script>
        ";
    }


    /**
     *
     * cetak LEMBAR EKSPEDISI
     *
     **/
    function action_persuratan_suratmasukcetakle($id)
    {
        $this->blankpage = true;

        $this->db_query("SELECT * FROM persuratan_ms_tujuanakhir");
        while ( $row = $this->db_fetch() ) { $arr_ms_tujuanakhir[$row['id']] = $row['name']; }

        // get ekspedisi ID
        $this->db_query("INSERT INTO persuratan_ekspedisisuratmasuk (ms_tujuanakhir_id, tanggalcetak) VALUES ('{$id}', NOW())");
        $ekspedisi_ID = $this->db_last_insert_id;

        $this->v .= "
<html>
<head>
    <meta http-equiv='content-type' content='text/html; charset=windows-1252'>
    <link type='text/css' href='".$this->basepath."dist/css/cetakstyle.css' rel='stylesheet' media='screen, print, projection'>
    <title>Tanda terima surat</title>
</head>
<body>
    <style>
    .noborder tr td { border:0px; }
    table tr th, table tr td { font-size:10px; }
    tr td:nth-child(2) {white-space: nowrap;}
    tr td:nth-child(3) {white-space: nowrap;}
    </style>
    <div align='center'>
        <h2>TANDA TERIMA EKSPEDISI SURAT</h2>
        <table class='list' style='width:95%; padding:10px; align:center;'>
            <tbody>
                <tr>
                    <td colspan='12' style='border:0px;' align='right'>
                        <table class='noborder' align='right'>
                            <tbody>
                                <tr><td style='width:50%;'></td><td>Pengiriman ke Unit</td><td width='1px'>:</td><td width='300px;'><b style='font-size:12pt;'><u>".$arr_ms_tujuanakhir[$id]."</u></b></td></tr>
                                <tr><td style='width:50%;'></td><td>Tanggal</td><td width='1px'>:</td><td width='300px;'>".date('d/n/Y')."</td></tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th>No</th>
                    <th>No & Tgl Agenda</th>
                    <th>No & Tgl Surat</th>
                    <th>Asal</th>
                    <th>Perihal</th>
                </tr>
        ";

        $this->db_query("SELECT * , DATE_FORMAT(tglagenda, '%d-%m-%Y') AS tglagenda, DATE_FORMAT(tglpembukuan, '%d-%m-%Y') AS tglpembukuan, DATE_FORMAT(tglsuratintern, '%d-%m-%Y') AS tglsuratintern, DATE_FORMAT(tglkma, '%d-%m-%Y') AS tglkma, DATE_FORMAT(tglsuratpengirim, '%d-%m-%Y') AS tglsuratpengirim, DATE_FORMAT(tglmasuktuadawas, '%d-%m-%Y') AS tglmasuktuadawas, DATE_FORMAT(tglkeluar, '%d-%m-%Y') AS tglkeluar FROM persuratan_suratmasuk 
            WHERE ms_flowstat_id = 102 AND ms_tujuanakhir_id = {$id}", array());
        $no = 1;
        if ($this->db_numrows() > 0) {
            while ($row = $this->db_fetch()) {
                $this->v .= "
                            <tr>
                                <td>".$no."</td>
                                <td>".$row[noagenda]."</br>".$row[tglagenda]."</td>
                                <td>".$row[nosuratintern]."</br>".$row[tglsuratintern]."</td>
                                <td>".$row[namapengirim]."<br>".$row[asalsuratpengirim]."</td>
                                <td>".$row[perihal]."</td>
                            </tr>";
                $no++;
            }
        }
        $this->v .= "
                <tr>
                    <td colspan='12' style='border:0px;'>
                        <div style='float:left; margin-top:30px;'>
                            <div align='center'>
                            </div>
                        </div>
                        <div style='float:right;'>
                            <table class='noborder' align='right'>
                                <tbody>
                                    <tr>
                                        <td width='50%;'></td>
                                        <td>Diterima tanggal</td>
                                        <td width='1px'>:</td>
                                        <td>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ".date('Y')."</td>
                                    </tr>
                                    <tr>
                                        <td width='50%;'></td>
                                        <td></td>
                                        <td width='1px'></td>
                                        <td>
                                            Jumlah (<b>".($no-1)."</b>) surat<br>
                                            <br>Nama :
                                            <br>
                                            <br>
                                            <br>
                                            <br>...........................................
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
            ";
        $res = $this->db_query("UPDATE persuratan_suratmasuk 
            SET ms_flowstat_id = 103, ekspedisisuratmasuk_id = '{$ekspedisi_ID}'
            WHERE ms_tujuanakhir_id='{$id}' AND ms_flowstat_id = 102");
    }
}