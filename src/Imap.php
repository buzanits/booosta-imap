<?php
namespace booosta\imap;

use \booosta\Framework as b;
b::init_module('imap');

class Imap extends \booosta\base\Module
{ 
  use moduletrait_imap;

  protected $mbox, $account;
  protected $hoststr, $serverstr;
  protected $folderlist;
  protected $msg_num;
  protected $raise_error;
  protected $dirty = false;


  public function __construct($server, $user, $password, $options = false)
  {
    if(!is_callable("imap_open")) $this->raise_error('IMAP support in PHP seems to be missing');

    parent::__construct();

    if(is_bool($options)) $this->raise_error = $options;
    else $this->raise_error = $options['raise_error'] ? true : false;


    if($options['tls']):
      if(!strstr($server, ':')) $server .= ':993';

      if($options['novalidate-cert']) $this->hoststr = '{' . $server . '/imap/ssl/novalidate-cert}';
      else $this->hoststr = '{' . $server . '/imap/ssl/validate-cert}';
    else:
      if(!strstr($server, ':')) $server .= ':143';
      $this->hoststr = '{' . $server . '/notls}';
    endif;

    $this->serverstr = $this->hoststr . 'INBOX';

    $this->mbox = \imap_open($this->serverstr, $user, $password, 0, 1);

    if($this->mbox === false):
      $msg = imap_last_error();
      $this->error($msg);
    else:
      $this->folderlist = \imap_list($this->mbox, $this->serverstr, '*');
      $this->msg_num = \imap_num_msg($this->mbox);
    endif;
  }

  public function after_instanciation()
  {
    $error = $this->error();
    if($error && $this->raise_error && is_callable([$this->topobj, 'raise_error']))
      $this->topobj->raise_error($error);
  }

  public function __destruct()
  {
    if($this->dirty) $this->expunge();
    @imap_close($this->mbox);
  }

  public function get_mail_message($num)
  {
    $info = \imap_headerinfo($this->mbox, $num);
    $sender = $info->fromaddress;
    $recipient = $info->toaddress;
    $subject = $info->subject;
    $rawtext = \imap_body($this->mbox, $num);

    $d = date_parse($info->date);
    $dtime = sprintf("%d-%02d-%02d %02d:%02d:%02d\n",
      $d['year'], $d['month'], $d['day'], $d['hour'], $d['minute'], $d['second']);

    return new mail_message($sender, $recipient, $subject, $rawtext, $dtime);
  }

  public function get_msg_num() { return $this->msg_num; }
  public function get_folderlist() { return $this->folderlist; }
  public function last_error() { return imap_last_error(); }
  public function expunge() { return \imap_expunge($this->mbox); }
  
  public function move_mail($num, $destination) 
  { 
    $this->dirty = true;
    return \imap_mail_move($this->mbox, $num, $destination); 
  }


  public function get_last_message($delete = false)
  {
    $result = $this->get_mail_message($this->msg_num);
    if($delete) $this->delete_message($this->msg_num);
    return $result;
  }

  public function create_folder($name)
  {
    return \imap_createmailbox($this->mbox, "$this->serverstr/$name");
  }

  public function delete_message($num, $expunge = true)
  {
    \imap_delete($this->mbox, $num);
    if($expunge) \imap_expunge($this->mbox);
  }

  public function get_size($bytes = false)
  {
    $info = \imap_get_quotaroot($this->mbox, 'INBOX');
    #\booosta\debug($info);
    $kilobytes = $info['STORAGE']['usage'];

    if($bytes) return $kilobytes * 1024;
    return $kilobytes / 1024;   // return megabytes
  }
}


class mail_message
{
  protected $sender, $recipient, $subject, $rawtext, $dtime;

  public function __construct($sender = null, $recipient = null, $subject = null, $rawtext = null, $dtime = null)
  {
    $this->sender = \mb_decode_mimeheader($sender);
    $this->recipient = \mb_decode_mimeheader($recipient);
    $this->subject = \mb_decode_mimeheader($subject);
    $this->rawtext = $rawtext;
    $this->dtime = $dtime;
  }

  public function set_sender($val) { $this->sender = \mb_decode_mimeheader($val); }
  public function set_recepient($val) { $this->recipient = \mb_decode_mimeheader($val); }
  public function set_recipient($val) { $this->recipient = \mb_decode_mimeheader($val); }
  public function set_subject($val) { $this->subject = \mb_decode_mimeheader($val); }
  public function set_rawtext($val) { $this->rawtext = $val; }
  public function set_text($val) { $this->rawtext = $val; }

  public function get_sender() { return $this->sender; }
  public function get_recepient() { return $this->recipient; }
  public function get_recipient() { return $this->recipient; }
  public function get_subject() { return $this->subject; }
  public function get_rawtext() { return $this->rawtext; }
  public function get_text() { return imap_qprint($this->rawtext); }
  public function get_dtime() { return $this->dtime; }
}
