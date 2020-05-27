<?php
/**
 * @package  Raw Updated Seed
 */

class RAWseed
{
 

               //Loop this function until it runs out.       
                 public function  priority_cast( $id , $section ) {
                           global $wpdb;      
                           $rqFEED="SELECT @rn := @rn+1 AS priority, id, section, site, rss, url, catagory, enable, last_post_date FROM ap_section_feeds CROSS JOIN (SELECT @rn := 0) AS v1 HAVING priority=".$id." AND section=\"".$section."\" ORDER BY last_post_date DESC;";  
                           $results=$wpdb->get_row( $rqFEED , ARRAY_A );                     
                           return $results;
                 }
 
                 // When find that current date is changed to newer date update FEEd data.
                 public function  update_timestamp( $id , $newtimestamp ) {
                           global $wpdb;
                           $fdate = strtotime( $newtimestamp );
                           $fmtdate = date('Y-m-d H:m:s', $fdate );
                           $udFEED="UPDATE `ap_section_feeds` SET `last_post_date` = '".$fmtdate."' WHERE id=".$id.";";                    
                           $result = $wpdb->query( $udFEED );                       
                           }



}