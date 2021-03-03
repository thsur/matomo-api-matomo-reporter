<?php

namespace Writer;

class Writer {
    
    /**
     * File system directory to write to.
     * 
     * @var String
     */
    private $dir;

    /**
     * Delimiter to use in csv files.
     * 
     * @var String
     */
    private $delimiter;

    /**
     * Get headers for writing csv.
     *  
     * @param  Array  $data 
     * @return Array
     */
    private function getHeaders(array $data){

        foreach ($data as $set) {

            return array_keys($set);
        }
    }

    /**
     * Write a JSON file.
     * 
     * @param  String $fn   
     * @param  Array  $data 
     * @return Writer
     */
    public function toJson(string $fn, array $data) {

        file_put_contents(

            $this->dir.'/'.$fn, 
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );

        return $this;
    }

    /**
     * Write an Excel-compatible CSV file.
     *
     * Cf.:
     * https://stackoverflow.com/a/49561209/3323348
     * 
     * @param  String  $fn       
     * @param  Array   $data     
     * @param  Boolean $compound 
     * @return Void
     */
    public function toExcelCsv(string $fn, array $data) {

        // Get headers & rows

        $headers = $this->getHeaders($data);
        $rows    = $data;

        // Create file and make it writable

        $file = fopen(STORAGE_DIR.'/'.$fn, 'w');

        // Add BOM to fix UTF-8 in Excel

        fputs($file, $bom = (chr(0xEF).chr(0xBB).chr(0xBF)));

        // Headers

        fputcsv($file, $headers, $this->delimiter);

        // Rows

        foreach ($rows as $row) {

            fputcsv($file, $row, $this->delimiter);
        }

        // Close file

        fclose($file);

    }

    public function __construct(string $dir, string $delimiter = ';'){

        $this->dir       = $dir;
        $this->delimiter = $delimiter;
    }
}