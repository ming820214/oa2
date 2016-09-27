<?php
namespace Home\Hongwen;
class ExportExcel{
    public $excelObj = null;
    public $config = array(
        'title' => '洪文教育',
    );

    function __construct($config = array()) {
        $this->config = array_merge($this->config, $config);


    }

    public function setTableHead($data){

    }

    public function export($data, $head = array()){
        $table = '<table border="1">';

        if(!empty($head)){
            $table .= '<thead><tr>';
            foreach($head as $h){
                $table .= "<th>{$h[1]}</th>";
            }
            $table .= '</tr></thead>';
        }

        $table .= '<tbody>';
        foreach($data as $record){
            $table .= '<tr>';
            if(!empty($head)){
                foreach($head as $h) {
                    $table .= "<td>{$record[$h[0]]}</td>";
                }
            }else{
                foreach($record as $value){
                    $table .= "<td>{$value}</td>";
                }
            }
            $table .= '</tr>';
        }
        $table .= '</tbody>';
        $table .= '</table>';

        $html = "<html>
                    <head>
                        <meta charset=\"UTF-8\">
                        <title>{$this->config['title']}</title>
                    </head>
                    <body>
                        {$table}
                    </body>
                    </html>
        ";

        return $html;
    }
}
