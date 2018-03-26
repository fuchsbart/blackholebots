<?php
/*
Title:        Blackhole for Bad Bots
Description:  Automatically trap and block bots that don't obey robots.txt rules
Project URL:  http://perishablepress.com/blackhole-bad-bots/
Author:       Jeff Starr (aka Perishable)
Author:       Petr Hucik (petr@getdatakick.com)
Version:      4.0
License:      GPLv2 or later
License URI:  https://www.gnu.org/licenses/gpl-2.0.txt
*/
namespace Blackhole;

require_once BLACKHOLE_BOTS_ROOT . '/classes/utils.php';
require_once BLACKHOLE_BOTS_ROOT . '/classes/ip-address.php';
require_once BLACKHOLE_BOTS_ROOT . '/classes/whois.php';
require_once BLACKHOLE_BOTS_ROOT . '/model/blacklist.php';

use \Db;
use \Mail;
use \Configuration;
use \BlackholeBlacklist;

class BlackholeBotsCore extends \Module {

  public function __construct() {
    $this->name = 'blackholebots';
    $this->tab = 'export';
    $this->version = '1.0.0';
    $this->author = 'DataKick';
    $this->need_instance = 0;
    $this->bootstrap = true;

    parent::__construct();
    $this->displayName = $this->l('Blackhole for Bad Bots');
    $this->description = $this->l("Automagically ban bad bots who don't follow robots.txt guidelines");
    $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
  }

  public function install($createTables=true) {
    return (
      parent::install() &&
      $this->installDb($createTables) &&
      $this->registerHook('displayHeader') &&
      $this->registerHook('displayFooter')
    );
  }

  public function uninstall($dropTables=true) {
    return (
      $this->uninstallDb($dropTables) &&
      $this->unregisterHook('displayHeader') &&
      $this->unregisterHook('displayFooter') &&
      parent::uninstall()
    );
  }

  public function reset() {
    return (
      $this->unregisterHook('displayHeader') &&
      $this->unregisterHook('displayFooter') &&
      $this->registerHook('displayHeader') &&
      $this->registerHook('displayFooter')
    );
  }


  private function installDb($create) {
    if (! $create) {
      return true;
    }
    return $this->executeSqlScript('install');
  }

  private function uninstallDb($drop) {
    if (! $drop) {
      return true;
    }
    return $this->executeSqlScript('uninstall');
  }

  public function executeSqlScript($script) {
    $file = BLACKHOLE_BOTS_ROOT . '/sql/' . $script . '.sql';
    if (! file_exists($file)) {
      return false;
    }
    $sql = file_get_contents($file);
    if (! $sql) {
      return false;
    }
    $sql = str_replace(['PREFIX_', 'ENGINE_TYPE', 'CHARSET_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_, 'utf8'], $sql);
    $sql = preg_split("/;\s*[\r\n]+/", $sql);
    foreach ($sql as $statement) {
      $stmt = trim($statement);
      if ($stmt) {
        if (!Db::getInstance()->execute($stmt)) {
          return false;
        }
      }
    }
    return true;
  }

  private function getTrapUrl() {
    return '/blackhole/';
  }

  private function isInTrap() {
    $uri = Utils::sanitize($_SERVER['REQUEST_URI']);
    $path = explode('?', $uri)[0];
    $path = rtrim($path, '/') . '/';
    return (strpos($path, $this->getTrapUrl()) !== false);
  }

  // business logic
  public function hookDisplayHeader($params) {
    $ip = IPAddress::get();
    if ($ip->isValid()) {
      if ($this->isInTrap()) {
        BlackholeBlacklist::trap($ip);
        $whois = Whois::getInfo($ip);
        $this->sendEmail($ip, $whois);
        $this->forbidden($ip, $whois);
      } else if (BlackholeBlacklist::inBlacklist($ip)) {
        $this->forbidden($ip, Whois::getInfo($ip));
      }
    }
  }

  public function hookDisplayFooter() {
    return '<div style="display:none"><a rel="nofollow" href="'.$this->getTrapUrl().'">Do NOT follow this link or you will be banned from the site!</a></div>';
  }

  private function sendEmail(IPAddress $ip, $whois) {
    $lang = 1;
    $email = Configuration::get('PS_SHOP_EMAIL');
    $data = [
      '{ip}' => $ip->getAddress(),
      '{whois}' => $whois
    ];
    $dir =  BLACKHOLE_BOTS_ROOT . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR;
    Mail::Send($lang, 'blackhole', Mail::l('Bad Bot Alert!', $lang), $data, $email, null, null, null, null, null, $dir, false);
  }

  private function forbidden(IPAddress $ip, $whois) {
    header('HTTP/1.0 403 Forbidden');
    $this->context->smarty->assign([
      'css' => $this->_path . '/views/css/blackhole.css',
      'ip' => $ip->getAddress(),
      'whois' => $whois
    ]);
    echo $this->display(BLACKHOLE_BOTS_ROOT, 'blackhole.tpl');
    exit();
  }

}
