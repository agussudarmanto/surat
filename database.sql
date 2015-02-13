CREATE DATABASE IF NOT EXISTS `surat` USE `surat`;

CREATE TABLE IF NOT EXISTS `persuratan_ms_arsip` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `persuratan_ms_arsip` (`id`, `kode`, `name`, `created`, `modified`, `ipaddress`) VALUES
	(1, 'T', 'Diteruskan', NULL, NULL, NULL),
	(2, 'A', 'Arsip', NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS `persuratan_ms_flowstat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `persuratan_ms_flowstat` (`id`, `kode`, `name`, `created`, `modified`, `ipaddress`) VALUES
	(101, 'suratmasuk', 'Surat Masuk - Input data', NULL, NULL, NULL),
	(102, 'suratmasuk', 'Surat Masuk - Cetak Lembar Disposisi', NULL, NULL, NULL),
	(103, 'suratmasuk', 'Surat Masuk - Cetak Lembar Ekspedisi', NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS `persuratan_ms_sifatsurat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `persuratan_ms_sifatsurat` (`id`, `kode`, `name`, `created`, `modified`, `ipaddress`) VALUES
	(1, 'JS1', 'Biasa', NULL, NULL, NULL),
	(2, 'JS2', 'Penting', NULL, NULL, NULL),
	(3, 'JS3', 'Rahasia', NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS `persuratan_ms_tipesurat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `persuratan_ms_tipesurat` (`id`, `kode`, `name`, `created`, `modified`, `ipaddress`) VALUES
	(1, '/Bua.6/Hs/', 'Surat masuk', NULL, NULL, NULL),
	(2, '/Bua.6/Hs/Hm.00/', 'Memo', NULL, NULL, NULL),
	(3, '/SK/Bua.6/Hs/', 'Surat keputusan', NULL, NULL, NULL),
	(4, '/S.Kel/Bua.6/Hs.02.3/', 'Surat keluar', NULL, NULL, NULL),
	(5, '/Bua.6/Hs/', 'Surat tugas', NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS `persuratan_ms_tujuanakhir` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `persuratan_ms_tujuanakhir` (`id`, `kode`, `name`, `created`, `modified`, `ipaddress`) VALUES
	(1, NULL, 'Kepala Bagian Perturan Perundang-Undangan', NULL, NULL, NULL),
	(2, NULL, 'Kepala Bagian Hubungan Antar Lembaga', NULL, NULL, NULL),
	(3, NULL, 'Kepala Bagian Perpustakaan dan Layanan Informasi', NULL, NULL, NULL),
	(4, NULL, 'Kepala Bagian Pemeliharaan Sarana Informatika', NULL, NULL, NULL),
	(5, NULL, 'Kepala Bagian Pengembangan Sistem Informatika', NULL, NULL, NULL),
	(6, NULL, 'Kepala Sub Bagian Tata Usaha Biro', NULL, NULL, NULL);

CREATE TABLE IF NOT EXISTS `persuratan_nomorsurat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ms_tipesurat_id` tinyint(4) DEFAULT NULL,
  `year` varchar(4) DEFAULT NULL,
  `nextid` int(11) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `persuratan_nomorsurat` (`id`, `ms_tipesurat_id`, `year`, `nextid`, `created`, `modified`, `ipaddress`) VALUES
	(1, 1, '2015', 1, '2014-10-30 10:02:28', '2014-11-03 15:11:57', NULL),
	(2, 2, '2015', 1, '2014-10-30 10:02:44', '2014-10-31 10:02:03', NULL),
	(3, 3, '2015', 1, '2014-10-30 10:03:04', '2014-10-30 10:03:04', NULL),
	(4, 4, '2015', 1, '2014-10-30 10:03:18', '2014-10-30 10:03:19', NULL),
	(5, 5, '2015', 1, '2014-10-30 10:03:35', '2014-10-30 10:03:36', NULL);

CREATE TABLE IF NOT EXISTS `persuratan_suratkeluar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ms_flowstat_id` tinyint(4) DEFAULT NULL,
  `ms_tipesurat_id` tinyint(4) DEFAULT NULL,
  `nomorsurat` varchar(255) DEFAULT NULL,
  `tglsurat` date DEFAULT NULL,
  `perihal` text,
  `tujuan` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `persuratan_suratmasuk` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ms_flowstat_id` tinyint(4) DEFAULT NULL,
  `ms_tipesurat_id` tinyint(4) DEFAULT NULL,
  `ms_sifatsurat_id` tinyint(4) DEFAULT NULL,
  `ms_tujuanakhir_id` tinyint(4) DEFAULT NULL,
  `ms_arsip_id` tinyint(4) DEFAULT NULL,
  `noagenda` varchar(255) DEFAULT NULL,
  `tglagenda` date DEFAULT NULL,
  `nosurat` varchar(255) DEFAULT NULL,
  `tglsurat` date DEFAULT NULL,
  `asalsurat` varchar(255) DEFAULT NULL,
  `perihal` text,
  `namapengirim` varchar(255) DEFAULT NULL,
  `tujuansurat` varchar(255) DEFAULT NULL,
  `disposisipimpinan` text,
  `lampiran` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `user_ms_group` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `admin` varchar(1) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `user_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `password` varchar(50) DEFAULT NULL,
  `namalengkap` varchar(100) DEFAULT NULL,
  `nip` varchar(18) DEFAULT NULL,
  `jabatan` varchar(100) DEFAULT NULL,
  `ms_group_id` tinyint(4) DEFAULT NULL,
  `lastlogin` datetime DEFAULT NULL,
  `lastloginip` varchar(18) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `user_user` (`id`, `email`, `name`, `password`, `namalengkap`, `nip`, `jabatan`, `ms_group_id`, `lastlogin`, `lastloginip`, `created`, `modified`, `ipaddress`) VALUES
	(1, NULL, 'siti', '827ccb0eea8a706c4c34a16891f84e7b', 'Siti Salbiah', NULL, NULL, 1, NULL, NULL, NULL, NULL, NULL),
	(2, NULL, 'indah', '827ccb0eea8a706c4c34a16891f84e7b', 'Indah', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(3, NULL, 'affan', '827ccb0eea8a706c4c34a16891f84e7b', 'Affan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
	(4, NULL, 'slamet', '827ccb0eea8a706c4c34a16891f84e7b', 'Slamet', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);
