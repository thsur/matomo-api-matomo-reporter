<?php

namespace Writer;

/**
 * PhpOffice spreadsheet wrapper.
 * 
 * For PHPOffice spreadsheet docs, cf.:
 * https://phpspreadsheet.readthedocs.io/en/latest/
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExcelWriter extends Writer{
    
    /**
     * Get cols as headers.
     *  
     * @param  Array  $data 
     * @return Array
     */
    private function getHeaders(array $groups){

        $group   = array_pop($groups);
        $headers = [];

        foreach ($group as $set) {

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
    public function getJson(string $fn) {

        return json_decode(file_get_contents($fn), true);
    }

    /**
     * Write an Excel file.
     *
     * The given array is expected to be a simple map of a data source file.
     * 
     * For each associated named key (['some-name' => path_to_data_source_file]),
     * a new worksheet is created, named after the key.
     *
     * Each worksheet is populated with the contents of the data source files. 
     * Of these, each is expected to be similar to all others in structure.
     *
     * Source files should be a simple map of rows and associative data sets. 
     * Both headers are derived from those sets. 
     * 
     * Cf.:
     * https://stackoverflow.com/a/49561209/3323348
     * 
     * @param  String  $fn       
     * @param  Array   $data     
     * @return Void
     */
    public function write(string $fn, array $map, $sort_tab_names = true) {

        // Collect tab names & their data
        
        $tabs = [];

        foreach ($map as $name => $src) {
            
            $tabs[$name] = $this->getJson($src);
        }

        if ($sort_tab_names) {

            ksort($tabs); 
        }

        // Get headers

        $headers = $this->getHeaders($tabs);

        // Prepare spreadsheet

        $spreadsheet = new Spreadsheet();

        // Create worksheets

        foreach (array_keys($tabs) as $name) {

            $spreadsheet->addSheet(new Worksheet($spreadsheet, "{$name}"));
        }

        // Remove default tab/worksheet
        
        $spreadsheet->removeSheetByIndex(0);

        // Write data per tab/worksheet

        foreach (array_keys($tabs) as $name) {

            $sheet = $spreadsheet->setActiveSheetIndexByName($name);
            
            // Get row counter

            $row  = 0;

            // Write headers

            $sheet->fromArray($headers, null, 'A1');
            $row++;

            // Write data
            
            $data = $tabs[$name];

            foreach ($data as $set) {

                $row++;
                $sheet->fromArray(array_values($set), null, 'A'.$row);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($this->dir.'/'.$fn);
    }
}