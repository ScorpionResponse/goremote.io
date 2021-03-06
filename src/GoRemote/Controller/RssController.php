<?php
namespace GoRemote\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RssController
{
    public function mainAction(Application $app)
    {
    	$latestJobs = $app['db']->fetchAll('select jobs.*, unix_timestamp(jobs.dateadded) as dateadded_unixtime, companies.name as companyname, companies.twitter as companytwitter, companies.url as companyurl, companies.logo as companylogo, sources.name as sourcename, sources.url as sourceurl from jobs inner join companies using(companyid) inner join sources using(sourceid) where jobs.dateadded > UTC_TIMESTAMP() - INTERVAL 1 MONTH and jobs.datedeleted=0 order by jobs.dateadded desc limit 70');
    	$rss = <<<RSS
<?xml version="1.0" encoding="UTF-8"?>
<rss xmlns:dc="http://purl.org/dc/elements/1.1/" version="2.0">
  <channel>
    <title>All Remote Jobs In One Place</title>
    <link>https://goremote.io/rss</link>
    <description>Recent jobs aggregated on GoRemote.io</description>
    <language>en-US</language>
    <ttl>180</ttl>
RSS;
		// TODO Create a class to get latestjobs so we don't duplicate the query above
		// TODO Use twig for RSS as view is in the controller :(
		foreach ($latestJobs as $job) {
			$job['dateadded'] = date('r', strtotime($job['dateadded']));
			$job['position'] = str_replace('&ndash', '', htmlentities($job['position']));
			$job['companyname'] = htmlentities($job['companyname']);
			$image = (!empty($job['companylogo'])) ? "<img src='{$job['companylogo']}'>" : '';
			$rss .= <<<RSS

			<item>
				<title>{$job['position']} @ {$job['companyname']}</title>
				<description>
					<![CDATA[
						{$image}
						{$job['description']}
					]]>
				</description>
				<pubDate>{$job['dateadded']}</pubDate>
				<guid>https://goremote.io/job/{$job['jobid']}</guid>
				<link>https://goremote.io/job/{$job['jobid']}</link>
			</item>
RSS;
		}

		$rss .= <<<RSS
  </channel>
</rss>
RSS;

		return new Response($rss, 200, array('Content-Type' => 'application/xml'));
    }
}
