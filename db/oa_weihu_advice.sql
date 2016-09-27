-- phpMyAdmin SQL Dump
-- version 4.1.14
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 2015-11-21 04:05:37
-- 服务器版本： 5.6.17
-- PHP Version: 5.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `hongwen_oa`
--

-- --------------------------------------------------------

--
-- 表的结构 `oa_weihu_advice`
--

CREATE TABLE IF NOT EXISTS `oa_weihu_advice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stuid` int(11) DEFAULT NULL,
  `teacher` varchar(5) DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `advice` varchar(200) DEFAULT NULL,
  `state` tinyint(4) DEFAULT NULL,
  `timee` timestamp NULL DEFAULT NULL,
  `pid` int(11) DEFAULT NULL,
  `node_type` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=13 ;

--
-- 转存表中的数据 `oa_weihu_advice`
--

INSERT INTO `oa_weihu_advice` (`id`, `stuid`, `teacher`, `type`, `advice`, `state`, `timee`, `pid`, `node_type`) VALUES
(1, 5528, '郝振华', '加课', '原因：测试；建议：看看', 0, '2015-11-17 07:02:51', 0, '0'),
(2, 5528, '雪梨', '1', '测试看看了一啦阿斯蒂芬', 0, '2015-11-17 16:00:00', 1, '1'),
(4, 5529, '郝振华', '3', '关联树', 1, '2015-11-17 22:31:11', 0, '0'),
(5, 5529, '蔡元培', '1', '可以可以', 0, '2015-11-18 05:17:29', 4, '1'),
(11, 5528, '郝振华', '其它', '原因：鱼鱼；建议：大幅答复', 0, '2015-11-20 09:16:07', 2, '0'),
(12, 5529, '郝振华', '加课', '原因：怎么样？；建议：可以了！', 0, '2015-11-21 02:20:36', 5, '0');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
