/**
* Copyright (C) 2017 Petr Hucik <petr@getdatakick.com>
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@getdatakick.com so we can send you a copy immediately.
*
* @author    Petr Hucik <petr@getdatakick.com>
* @copyright 2018 Petr Hucik
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/


CREATE TABLE IF NOT EXISTS `PREFIX_blackholebots_blacklist` (
  `id_address`         INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_add`           DATETIME NOT NULL,
  `date_upd`           DATETIME NOT NULL,
  `address`            VARCHAR(64),
  `cnt`                BIGINT,
  PRIMARY KEY (`id_address`),
  UNIQUE (`address`)
) ENGINE=ENGINE_TYPE DEFAULT CHARSET=CHARSET_TYPE;
