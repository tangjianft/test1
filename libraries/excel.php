<?php

/**
 * excel class file
 *
 * 用于生成excel文件操作
 * @author tommy <streen003@gmail.com>
 * @copyright  Copyright (c) 2010 Tommy Software Studio
 * @link http://www.doitphp.com
 * @license New BSD License.{@link http://www.opensource.org/licenses/bsd-license.php}
 * @version $Id: excel.class.php 1.3 2011-11-13 20:55:01Z tommy $
 * @package libraries
 * @since 1.0
 */
class Excel
{

    /**
     * EXCEL表格的xml代码
     *
     * @var string
     */
    protected $xmlTable;

    /**
     * EXCEL的标题xml代码
     *
     * @var string
     */
    protected $xmlMenu;

    /**
     * 构造函数
     *
     * @return void
     */
    public function __construct ()
    {}

    /**
     * 处理EXCEL中一行代码,相当于HTML中的行标签<tr></tr>
     *
     * @param array $data            
     * @return string
     */
    protected function handleRow ($data)
    {
        
        // 参数分析
        if (empty($data) || ! is_array($data)) {
            return false;
        }
        
        $xml = "<Row>\n";
        foreach ($data as $key => $value) {
            $xml .= ($key > 0 && empty($data[$key - 1])) ? $this->handleIndexCell(
                    $value, $key + 1) : $this->handleCell($value);
        }
        $xml .= "</Row>\n";
        
        return $xml;
    }

    /**
     * 处理EXCEL多行数据的代码.
     *
     * @param array $data            
     * @return string
     */
    protected function addRows ($data)
    {
        
        // 参数分析
        if (empty($data) || ! is_array($data) || ! is_array($data[0])) {
            return false;
        }
        
        $xmlArray = array();
        foreach ($data as $row) {
            $xmlArray[] = $this->handleRow($row);
        }
        
        return implode('', $xmlArray);
    }

    /**
     * 配置EXCEL表格的标题
     *
     * @param array $data
     *            所要生成的excel的标题,注:参数为数组
     * @return boolean
     */
    public function setMenu ($data)
    {
        
        // 参数分析
        if (empty($data) || ! is_array($data) || is_array($data[0]) ||
                 array_search('', $data)) {
            return false;
        }
        
        $xml = "<Row>\n";
        foreach ($data as $value) {
            $type = (is_numeric($data) && (substr($data, 0, 1) != 0)) ? 'Number' : 'String';
            $xml .= "<Cell><Data ss:Type=\"{$type}\">" . $value .
                     "</Data></Cell>\n";
        }
        $xml .= "</Row>\n";
        $this->xmlMenu = $xml;
        
        return true;
    }

    /**
     * 处理EXCEL表格的内容,相当于table.
     *
     * @param array $data            
     * @return string
     */
    public function setData ($data)
    {
        $xmlRows = $this->addRows($data);
        
        if (empty($xmlRows)) {
            if (empty($this->xmlMenu)) {
                return false;
            } else {
                $content = $this->xmlMenu;
            }
        } else {
            if (empty($this->xmlMenu)) {
                $content = $xmlRows;
            } else {
                $content = $this->xmlMenu . $xmlRows;
            }
        }
        
        return $this->xmlTable = "<Table>\n" . $content . "</Table>\n";
    }

    /**
     * 处理EXCEL表格信息代码
     *
     * @return string
     */
    protected function parseTable ()
    {
        $xmlWorksheet = "<Worksheet ss:Name=\"Sheet1\">\n";
        
        if (empty($this->xmlTable)) {
            $xmlWorksheet .= "<Table/>\n";
        } else {
            $xmlWorksheet .= $this->xmlTable;
        }
        
        $xmlWorksheet .= "</Worksheet>\n";
        
        return $xmlWorksheet;
    }

    /**
     * 处理EXCEL中的表格,相当于html中的标签<td></td>
     *
     * @param string $data            
     * @return string
     */
    protected function handleCell ($data)
    {
        
        // 参数分析
        if (empty($data) || is_array($data)) {
            return false;
        }
        
        $type = (is_numeric($data) && (substr($data, 0, 1) != 0)) ? 'Number' : 'String';
        
        return "<Cell><Data ss:Type=\"" . $type . "\">" . $data .
                 "</Data></Cell>\n";
    }

    /**
     * 处理EXCEL中CELL代码,当该CELL前的一个CELL内容为空时.
     *
     * @param string $data            
     * @param integer $key            
     * @return string
     */
    protected function handleIndexCell ($data, $key)
    {
        
        // 参数分析
        if (empty($data) || is_array($data)) {
            return false;
        }
        
        $type = (is_numeric($data) && (substr($data, 0, 1) != 0)) ? 'Number' : 'String';
        
        return "<Cell ss:Index=\"" . $key . "\"><Data ss:Type=\"" . $type . "\">" .
                 $data . "</Data></Cell>\n";
    }

    /**
     * 分析EXCEL的文件头
     *
     * @return tring
     */
    protected function parseHeader ()
    {
        return "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"
 xmlns:o=\"urn:schemas-microsoft-com:office:office\"
 xmlns:x=\"urn:schemas-microsoft-com:office:excel\"
 xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"
 xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
    }

    /**
     * 生成EXCEL文件并下载.
     *
     * @param string $fileName
     *            所要生成的excel的文件名,注:文件名中不含后缀名
     */
    public function download ($fileName)
    {
        
        // 参数分析
        if (empty($fileName)) {
            return false;
        }
        
        header('Pragma: no-cache');
        header("Content-Type: application/vnd.ms-excel; charset=utf-8");
        header(
                "Content-Disposition: inline; filename=\"" . $fileName . ".xls\"");
        $excelXml = $this->parseHeader() . $this->parseTable() . "</Workbook>";
        
        echo $excelXml;
    }

    /**
     * 构晰函数
     *
     * @return void
     */
    public function __destruct ()
    {
        exit();
    }
}