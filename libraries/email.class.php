<?php
/*
 * @Date 2011-1-12 @Author h2ero Email 122750707@qq.com Blog blog.h2ero.cn
 */
class HMail
{

    private $smtpsever; // smtp服务地址
    private $to; // 发送到
    private $username; // smtp登陆用户名
    private $pwd; // smtp登陆密码
    private $title; // subject name
    private $content; // 正文
    private $timeout; // 超时
    private $port = 25; // 端口
    private $newline = "\r\n"; // new line
    private $localdomain = "youdomain.com";
    
    private $charset; // 正文编码
    private $connection; // 连接句柄
    public $debug; // debug信息
    private $Cc; // 抄送';'隔开
    private $BCc; // 密送
    private $Ccc = NULL; // 取所有抄送
    private $BCcc = NULL; // 取所有密送
    private $CsendOnce = NULL; // 判断All Carbon Copy 是否发送成功
    private $BsendOnce = NULL;

    private $Attachment; // 附件,数组传入路径
    function __construct ($username, $pwd, $smtpsever, $timeout, 
            $charset = "gb2312", $localdomain = "bbrtv.com")
    {
        $this->username = $username;
        $this->pwd = $pwd;
        $this->smtpsever = $smtpsever;
        $this->timeout = $timeout;
        $this->charset = $charset;
        $this->localdomain = $localdomain;
    }

    function connect ()
    {
        $this->connection = fsockopen($this->smtpsever, $this->port, $errno, 
                $errstr, $this->timeout);
        if ($this->connection) {
            $this->debug .= "连接成功\n";
        } else {
            $this->debug .= "连接失败：错误为：$errstr错误代号：($errno)\n";
        }
        $this->debug .= $this->readResponse();
        // hi sever
        $this->sendCommand("HELO $this->localdomain");
        $this->debug .= $this->readResponse();
        // let's know each other
        $this->sendCommand("AUTH LOGIN");
        $this->debug .= $this->readResponse();
        // send usernam and pwd
        $this->sendCommand(base64_encode($this->username));
        $this->debug .= $this->readResponse();
        $this->sendCommand(base64_encode($this->pwd));
        $this->debug .= $this->readResponse();
        // FROM
        $this->sendCommand("MAIL FROM:$this->username");
        $this->debug .= $this->readResponse();
        // TO
        $this->sendCommand("RCPT TO:$this->to");
        $this->debug .= $this->readResponse();
        // send data
        $this->sendCommand("DATA");
        $this->debug .= $this->readResponse();
        // send header
        $this->sendHeader();
        // 分割线开始–boundary发送email内容分割线将content和attachment分割开
        $this->sendACommand("–boundary-example-1");
        // 发送ContentHeader
        $this->sendCHeader();
        // 发送正文
        $this->sendCommand($this->content);
        // set boundary
        $this->sendAllAttachment($this->Attachment);
        // 分割线结尾–boundary–
        $this->sendACommand("–boundary-example-1–");
        // end with .
        $this->sendCommand(".");
        $this->debug .= $this->readResponse();
        // quit
        $this->sendCommand("QUIT");
        $this->debug .= $this->readResponse();
    }

    function send ($to, $title, $content, $Cc = NULL, $BCc = NULL, $Attachment = NULL)
    {
        $this->to = $to;
        $this->title = $title;
        $this->content = $content;
        $this->Attachment = $Attachment;
        // $this->connect(); 放在这儿则To不能看到Cc只能Cc互相看到
        // send Cc
        if ($Cc) {
            empty($this->Cc) ? $this->Cc = $Cc : $this->skip();
            empty($this->Ccc) ? $this->Ccc = $this->Cc : $this->skip();
            if (! $this->CsendOnce) {
                $temp = explode(";", $this->Ccc);
                // 循环输出仅仅一次
                foreach ($temp as $key => $value) {
                    $this->CsendOnce = 1;
                    $this->send($value, $this->title, $this->content, "", "", 
                            $this->Attachment);
                }
            }
        }
        
        // send BCc
        if ($BCc) {
            empty($this->BCc) ? $this->BCc = $BCc : $this->skip();
            empty($this->BCcc) ? $this->BCcc = $this->BCc : $this->skip();
            if (! $this->BsendOnce) {
                $temp = explode(":", $this->BCcc);
                foreach ($temp as $key => $value) {
                    $this->BsendOnce = 1;
                    $this->send($value, $this->title, $this->content, "", "");
                }
            }
        }
        $this->connect(); // 将connect放在最后为了让第一个to能看到Cc的用户,若反正Cc前则To不能看到
    }

    function sendCommand ($code)
    {
        // sendCommand end with \r\n
        fputs($this->connection, $code . $this->newline);
    }

    function sendACommand ($code)
    {
        // sendCommand end with \r\n
        empty($this->Attachment) ? $this->skip() : fputs($this->connection, 
                $code . $this->newline);
    }

    function readResponse ()
    {
        $data = "";
        while ($str = fgets($this->connection, 4096)) {
            $data .= $str;
            if (substr($str, 3, 1) == " ") {
                break;
            }
        }
        return $data . "\n";
    }

    function sendHeader ()
    {
        $this->sendCommand("Date: " . date("D, j M Y G:i:s") . " +0700");
        $this->sendCommand("From:<$this->username>"); // 对于qq邮箱此项不能填写其他的
        $this->sendCommand("To:<$this->to>"); // 这儿无所谓
        $this->sendCommand("Subject:$this->title"); // 邮件的标题
        empty($this->Cc) ? $this->skip() : $this->sendall("Cc"); // Carbon Copy
        empty($this->BCc) ? $this->skip() : $this->sendall("BCc"); // Blind Carbon
                                                               // Copy
        $this->sendCommand("MIME-Version: 1.0");
        $this->sendCommand(
                "Content-type: multipart/mixed;boundary=boundary-example-1; charset=$this->charset");
        // end with \r\n\r\n
        $this->sendCommand("\r\n");
    }

    function sendCHeader ()
    {
        $this->sendACommand("Content-type:text/html; charset=gb2312");
        // end with \r\n\r\n
        $this->sendACommand("\r\n");
    }

    function sendAttachment ($file)
    {
        $file = $file;
        $allmime = include ("mime.php");
        $filename = basename($file);
        $filetype = array_slice(explode(".", $filename), - 1, 1);
        $filetype = $filetype[0];
        foreach ($allmime as $key => $value) {
            if ($filetype == $key) {
                $filemime = $value;
                break;
            }
        }
        $Attachment = file_get_contents($file);
        $this->sendACommand("–boundary-example-1");
        $this->sendCommand("Content-Type:$filetype;name=$filename");
        $this->sendCommand("Content-disposition:attachment; filename=$filename");
        // base64传送 用bin不成功 或者可以用其他的8bit等
        $this->sendCommand("Content-Transfer-Encoding:base64");
        // Attachment header end
        $this->sendCommand("\r\n");
        $this->sendCommand(base64_encode($Attachment));
    }

    function sendAllAttachment ()
    {
        // 判断是否为空数组
        $file = $this->Attachment;
        empty($file) ? $temp = 0 : $temp = 1;
        if ($temp) {
            foreach ($file as $key => $value) {
                file_exists($value) ? $this->sendAttachment($value) : $this->skip();
            }
        }
    }

    function debug ()
    {
        echo $this->debug;
    }
    // just skip
    function skip ()
    {}
    // 循环输出抄送地址和密送地址于头部
    function sendall ($C)
    {
        $C = $C;
        empty($this->Ccc) ? $this->skip() : $Cc = explode(":", $this->Ccc);
        empty($this->BCcc) ? $this->skip() : $Cc = explode(":", $this->BCcc);
        foreach ($Cc as $key => $value) {
            $this->sendCommand("$C:$value");
        }
    }
}

?>