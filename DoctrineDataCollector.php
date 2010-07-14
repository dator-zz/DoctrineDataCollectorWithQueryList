<?php

namespace Symfony\Bundle\DoctrineBundle\DataCollector;

use Symfony\Components\HttpKernel\Profiler\DataCollector\DataCollector;
use Symfony\Components\DependencyInjection\ContainerInterface;

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * DoctrineDataCollector.
 *
 * @package    Symfony
 * @subpackage Bundle_DoctrineBundle
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class DoctrineDataCollector extends DataCollector
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function collect()
    {
        $this->data = array();
        if ($this->container->has('doctrine.dbal.logger')) {
            $this->data = array(
                'queries' => $this->container->getDoctrine_Dbal_LoggerService()->queries,
            );
        }
    }

    public function getQueryCount()
    {
        return count($this->data['queries']);
    }

    public function getQueries()
    {
        return $this->data['queries'];
    }

    public function formatSql($sql)
    {
        return preg_replace('/\b(UPDATE|SET|SELECT|FROM|AS|LIMIT|ASC|COUNT|DESC|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|ORDER BY|GROUP BY|IN|LIKE|DISTINCT|DELETE|INSERT|INTO|VALUES)\b/', '<span style="color:#62798F;font-weight:bold;">\\1</span>', $sql);
    }
    
    public function getSummary()
    {
        $queries = count($this->data['queries']);
        $greenColor = '#2d2';
        $redColor = '#d22';
        $oddColor = '#DDE4EB';
        $evenColor = '#C2C9CF';
        $duplicateColor = '#EFD1D2';
        $queriesColor = $queries < 10 ? $greenColor : $redColor;
        $toolbarCountIdenticalHtml = $summaryIdenticalHtml = $summaryIdenticalQuery = '';
        
        $sqlQueries = array_map(function($data)
        {
            return $data['sql'];
        }
        ,$this->data['queries']);
        
        $nonIdenticalQueries = array_unique($sqlQueries);
        $identicalQueries = array_diff_assoc($sqlQueries, $nonIdenticalQueries);
        $identicalQueriesCount = count(array_unique($identicalQueries));
        
        if($identicalQueriesCount)
        {
            $toolbarCountIdenticalHtml = sprintf('<span style="color: '.$redColor.'">(%s)</span>', $identicalQueriesCount); 
        }
        
        
        $inc = 0;
        $orderedQueriesList = '<ol>';
        
        foreach($sqlQueries as $sql)
        {
            $odd = ($inc % 2) ? $oddColor : $evenColor;
          
            if(in_array($sql,$identicalQueries))
            {
                $style = 'background-color:'.$duplicateColor.';padding:5px;';
            }
            else
            {
                $style = 'background-color:'.$odd.';padding:5px;';
            }
            
            $orderedQueriesList .= '<li style="'.$style.'">'.$this->formatSql($sql).'</li>';
            $inc++;
        }
        $orderedQueriesList .= '</ol>';
            
        if($identicalQueriesCount)
        {
            $summaryIdenticalQuery = '<ol>';
            $inc = 0;
            foreach(array_unique($identicalQueries) as $query)
            {
                $id = 1;
                foreach($identicalQueries as $q){
                    if($q == $query){
                        $id++;
                    }
                }
                $odd = ($inc % 2) ? $oddColor : $evenColor;
                $summaryIdenticalQuery .= '<li>This query is duplicated <strong>'.$id.' times</strong> : <br />'.$this->formatSql($query).' </li><br />';
                $inc++;
            }
            $summaryIdenticalQuery .= '</ol>';
            
            $summaryIdenticalHtml = sprintf('<p><h2>%s duplicated queries</h2></p>%s<hr style="border:1px solid #eee"/>', $identicalQueriesCount, $summaryIdenticalQuery);
        }
        return sprintf('<script type="text/javascript">
        function toggle(obj) {var el = document.getElementById(obj);if ( el.style.display != \'none\' ) {el.style.display = \'none\';}else {el.style.display = \'\';}}</script><img onclick="toggle(\'data_collector_query_list\')" style="margin-left: 10px; vertical-align: middle" alt="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAKlJREFUeNrsk0EOgyAQRT9KiLog7D0Ql+B4HsULeAHXQFwaiCGBCm1Nmi4a69a/IDNk5g+8ZEhKCcMwYFfCORGlFOgrSVJKNE0DxhgofV6HELBtG5xz8N6XuK7rUjOOYx5I3gbQWoNzDiEEuq5DjLE0LcsCYwystVjXFW3bou/74xkVLuqywfGFaZp+T6uqwmGe52+DPyB+GtwQb4h5q3aI6SREko+HAAMADJ+V5b1xqucAAAAASUVORK5CYII=" />
            <span style="color: %s">%d</span>&nbsp;%s<div style="height:400px;overflow:auto;display:none" id="data_collector_query_list">%s<h2>SQL Queries</h2><div>%s</div></div>
        ',  
            $queriesColor, 
            $queries, 
            $toolbarCountIdenticalHtml, 
            $summaryIdenticalHtml, 
            $orderedQueriesList
        );
    }

    public function getName()
    {
        return 'db';
    }
}
