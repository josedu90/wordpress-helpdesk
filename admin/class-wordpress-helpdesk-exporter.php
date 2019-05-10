<?php

class WordPress_Helpdesk_Exporter extends WordPress_Helpdesk
{
    protected $plugin_name;
    protected $version;

    /**
     * Construct Ticket Locator Admin Class
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    http://plugins.db-dzine.com
     * @param   string                         $plugin_name
     * @param   string                         $version
     */
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->notice = "";
    }

    /**
     * Init the Exporter Function
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return [type] [description]
     */
    public function init()
    {
        global $wordpress_helpdesk_options;
        $this->options = $wordpress_helpdesk_options;

        add_action('admin_notices', array($this, 'notice' ));

        if (! is_admin()) {
            $this->notice .= __('You are not an admin', 'wordpress-helpdesk');
            return false;
        }

        // Get Tickets
        $tickets = $this->get_tickets();
        if (empty($tickets)) {
            $this->notice .= __('No Tickets to Export', 'wordpress-helpdesk');
            return false;
        }

        // Build the Export
        if ($this->build_export($tickets)) {
            $this->notice .= __('Your Ticket Export is ready. The Download should start automatically.', 'wordpress-helpdesk');
        } else {
            $this->notice .= __('Something was wrong with the export generation ...', 'wordpress-helpdesk');
        }
    }

    /**
     * Get All Tickets for Export
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return [type] [description]
     */
    public function get_tickets()
    {
        $args = array(
            'posts_per_page'   => -1,
            'post_type'        => 'ticket',
            'post_status'      => 'publish',
        );
        $posts = get_posts($args);

        $prefix = 'wordpress_ticket_locator_';
        $this->possibleStati = get_terms(array( 'taxonomy' =>'ticket_status', 'hide_empty' => false));
        $this->possibleTypes = get_terms(array( 'taxonomy' =>'ticket_type', 'hide_empty' => false));
        $this->possiblePriorities = get_terms(array( 'taxonomy' =>'ticket_priority', 'hide_empty' => false));
        $this->possibleSystems = get_terms(array( 'taxonomy' =>'ticket_system', 'hide_empty' => false));
        
        $tickets = array();
        foreach ($posts as $post) {
            $id = $post->ID;
            $tickets[$id]['id'] = $id;
            $tickets[$id]['name'] = $post->post_title;
            $tickets[$id]['created'] = $post->post_date;
            $tickets[$id]['creator'] = get_userdata($post->post_author)->data->display_name;
            $tickets[$id]['agent'] = !empty(get_post_meta($id, 'agent', true)) ? get_userdata(get_post_meta($id, 'agent', true))->data->display_name : '';
            $tickets[$id]['source'] = get_post_meta($id, 'source', true);
            $tickets[$id]['description'] = $post->post_content;
            $tickets[$id]['link'] = get_permalink($id);

            $ticketStatus = wp_get_post_terms($id, 'ticket_status', array('fields' => 'slugs'));
            foreach ($this->possibleStati as $possibleStatus) {
                $status = $possibleStatus->slug;
                if (in_array($status, $ticketStatus)) {
                    $tickets[$id][$status] = 1;
                } else {
                    $tickets[$id][$status] = 0;
                }
            }

            $ticketType = wp_get_post_terms($id, 'ticket_type', array('fields' => 'slugs'));
            foreach ($this->possibleTypes as $possibleType) {
                $type = $possibleType->slug;
                if (in_array($type, $ticketType)) {
                    $tickets[$id][$type] = 1;
                } else {
                    $tickets[$id][$type] = 0;
                }
            }

            $ticketPriority = wp_get_post_terms($id, 'ticket_priority', array('fields' => 'slugs'));
            foreach ($this->possiblePriorities as $possiblePriority) {
                $priority = $possiblePriority->slug;
                if (in_array($priority, $ticketPriority)) {
                    $tickets[$id][$priority] = 1;
                } else {
                    $tickets[$id][$priority] = 0;
                }
            }

            $ticketSystem = wp_get_post_terms($id, 'ticket_system', array('fields' => 'slugs'));
            foreach ($this->possibleSystems as $possibleSystem) {
                $system = $possibleSystem->slug;
                if (in_array($system, $ticketSystem)) {
                    $tickets[$id][$system] = 1;
                } else {
                    $tickets[$id][$system] = 0;
                }
            }
        }

        return $tickets;
    }

    /**
     * Build the Export file
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @param   [type]                       $tickets [description]
     * @return  [type]                                [description]
     */
    public function build_export($tickets)
    {
        $excelExt = '.xlsx';
        $writer = 'Excel2007';

        $useExcel2007 = $this->get_option('excel2007');

        if ($useExcel2007 == "1") {
            $excelExt = '.xls';
            $writer = 'Excel5';
        }

        $objPHPExcel = new PHPExcel();


        // Set document properties
        $objPHPExcel->getProperties()->setCreator("DB-Dzine")
                                     ->setLastModifiedBy("DB-Dzine")
                                     ->setTitle("Ticket Export (".date('Y.m.d - H:i:s').")")
                                     ->setSubject("Tickets export")
                                     ->setDescription("Tickets export.")
                                     ->setKeywords("wordpress tickets");
        // Add some data
        // A note from the manual: In PHPExcel column index is 0-based while row index is 1-based. That means 'A1' ~ (0,1)
        $row = 2; // 1-based index
        $informationColumns = 7;
        $firstLine = true;
        foreach ($tickets as $fields) {
            $col = 0;
            if ($firstLine) {

                // Set header Data
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, __('Ticket Information', 'wordpress-helpdesk') );
                $objPHPExcel->getActiveSheet()->mergeCells('A1:H1');

                // Status
                $statiStartColumn = $informationColumns + 1;
                $statiEndColumn = $statiStartColumn + count($this->possibleStati) - 1;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($statiStartColumn, 1, __('Status', 'wordpress-helpdesk') );
                $objPHPExcel->getActiveSheet()->mergeCells($this->integerToLetter($statiStartColumn) . '1:' . $this->integerToLetter($statiEndColumn) . '1');

                // Type
                $typeStartColumn = $statiEndColumn + 1;
                $typeEndColumn = $typeStartColumn + count($this->possibleTypes) - 1;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($typeStartColumn, 1, __('Type', 'wordpress-helpdesk') );
                $objPHPExcel->getActiveSheet()->mergeCells($this->integerToLetter($typeStartColumn) . '1:' . $this->integerToLetter($typeEndColumn) . '1');

                // Priority
                $priorityStartColumn = $typeEndColumn + 1;
                $priorityEndColumn = $priorityStartColumn + count($this->possiblePriorities) - 1;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($priorityStartColumn, 1, __('Priority', 'wordpress-helpdesk') );
                $objPHPExcel->getActiveSheet()->mergeCells($this->integerToLetter($priorityStartColumn) . '1:' . $this->integerToLetter($priorityEndColumn) . '1');

                // System
                $systemStartColumn = $priorityEndColumn + 1;
                $systemEndColumn = $systemStartColumn + count($this->possibleSystems) - 1;
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($systemStartColumn, 1, __('System', 'wordpress-helpdesk') );
                $objPHPExcel->getActiveSheet()->mergeCells($this->integerToLetter($systemStartColumn) . '1:' . $this->integerToLetter($systemEndColumn) . '1');

                // Center and Bold 
                $objPHPExcel->getActiveSheet()->getStyle('A1:Z1')->getFont()->setBold(true);
                $objPHPExcel->getActiveSheet()->getStyle('A1:Z1')->getAlignment()->applyFromArray(
                    array('horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,)
                );
                // Set Keys
                $keys = array_keys($fields);
                foreach ($keys as $key) {
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $key);
                    $col++;
                }
                $row++;
                $col = 0;
                $firstLine = false;
            }
            foreach ($fields as $key => $value) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($col, $row, $value);
                $col++;
            }
            $row++;
        }

        // Rename worksheet
        $objPHPExcel->getActiveSheet()->setTitle('Export ('.date('Y.m.d - H.i.s').')');
        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);
        // Redirect output to a client’s web browser (Excel2007)
        // ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="tickets-export_' . date('Y-m-d_H-i-s') . $excelExt . '"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');
        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, $writer);
        ob_end_clean();
        $objWriter->save('php://output');
        exit();
        return true;
    }

    /**
     * Show a notice
     * @author Daniel Barenkamp
     * @version 1.0.0
     * @since   1.0.0
     * @link    https://plugins.db-dzine.com
     * @return [type] [description]
     */
    public function notice()
    {
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo $this->notice ?></p>
        </div>
        <?php
    }

    private function integerToLetter($c) 
    {
        $letter = '';
        $c = intval($c + 1);
        if ($c <= 0) return '';
                 
        while($c != 0){
           $p = ($c - 1) % 26;
           $c = intval(($c - $p) / 26);
           $letter = chr(65 + $p) . $letter;
        }
        
        return $letter;
            
    }

}