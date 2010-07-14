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
        $queriesColor = $queries < 10 ? '#2d2' : '#d22';
      
        $sqlQueries = array_map(function($data){
          return $data['sql'];
        },$this->data['queries']);
        
        $nonIdenticalQueries = array_unique($sqlQueries);
        $identicalQueries = array_diff_assoc($sqlQueries, $nonIdenticalQueries);
        $inc = 0;
        $orderedQueriesList = '<ol>';
        
        foreach($sqlQueries as $sql){
          $odd = ($inc % 2) ? 'DDE4EB' : 'C2C9CF';
          
          if(in_array($sql,$identicalQueries)){
            $style = 'background-color:#EFD1D2;padding:5px;';
          }else{
            $style = 'background-color:#'.$odd.';padding:5px;';
          }
            
          $orderedQueriesList .= '<li style="'.$style.'">'.$this->formatSql($sql).'</li>';
          $inc++;
        }
        $orderedQueriesList .= '</ol>';
        
        return sprintf('<script type="text/javascript">
        function toggle(obj) {var el = document.getElementById(obj);if ( el.style.display != \'none\' ) {el.style.display = \'none\';}else {el.style.display = \'\';}}</script><img onclick="toggle(\'data_collector_query_list\')" style="margin-left: 10px; vertical-align: middle" alt="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAKlJREFUeNrsk0EOgyAQRT9KiLog7D0Ql+B4HsULeAHXQFwaiCGBCm1Nmi4a69a/IDNk5g+8ZEhKCcMwYFfCORGlFOgrSVJKNE0DxhgofV6HELBtG5xz8N6XuK7rUjOOYx5I3gbQWoNzDiEEuq5DjLE0LcsCYwystVjXFW3bou/74xkVLuqywfGFaZp+T6uqwmGe52+DPyB+GtwQb4h5q3aI6SREko+HAAMADJ+V5b1xqucAAAAASUVORK5CYII=" />
            <span style="color: %s">%d</span><div style="height:200px;overflow:auto;display:none" id="data_collector_query_list"><h1>SQL Queries</h1><p>%s identical queries</p><div>%s</div></div>
        ', $queriesColor, $queries,count($identicalQueries), $orderedQueriesList);
    }

    public function getName()
    {
        return 'db';
    }
}
