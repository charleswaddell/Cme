<?php

require_once 'Admin/pages/AdminPage.php';
require_once 'CME/dataobjects/CMEEvaluationReportWrapper.php';

/**
 * @package   CME
 * @copyright 2011-2014 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 */
class CMEEvaluationReportDownload extends AdminPage
{
	// {{{ protected properties

	/**
	 * @var CMEEvaluationReport
	 */
	protected $report;

	// }}}

	// init phase
	// {{{ protected function initInternal()

	protected function initInternal()
	{
		parent::initInternal();
		$this->initReport();
	}

	// }}}
	// {{{ protected function initReport()

	protected function initReport()
	{
		$quarter = SiteApplication::initVar('quarter', null,
			SiteApplication::VAR_GET);

		if ($quarter === null ||
			preg_match('/^2[0-9]{3}-0[1-4]$/', $quarter) === 0) {
			throw new AdminNotFoundException('Invalid quarter.');
		}

		list($year, $quarter) = explode('-', $quarter, 2);

		$start_month = ((intval($quarter) - 1) * 3) + 1;

		$quarter = new SwatDate();
		$quarter->setTime(0, 0, 0);
		$quarter->setDate($year, $start_month, 1);
		$quarter->setTZ($this->app->default_time_zone);
		$quarter->toUTC();

		$type = SiteApplication::initVar(
			'type',
			null,
			SiteApplication::VAR_GET
		);

		$class_name = SwatDBClassMap::get('CMECreditType');
		$credit_type = new $class_name();
		$credit_type->setDatabase($this->app->db);
		if (!$credit_type->loadByShortname($type)) {
			throw new AdminNotFoundException('Invalid credit type');
		}

		$sql = sprintf(
			'select * from EvaluationReport
			where quarter = %s and credit_type = %s',
			$this->app->db->quote($quarter->getDate(), 'date'),
			$this->app->db->quote($credit_type->id, 'integer')
		);

		$this->report = SwatDB::query(
			$this->app->db,
			$sql,
			SwatDBClassMap::get('CMEEvaluationReportWrapper')
		)->getFirst();

		if ($this->report === null) {
			throw new AdminNotFoundException(
				sprintf(
					'Report not found for quarter %s.',
					$quarter->getDate()
				)
			);
		}

		$this->report->setFileBase('../../system/evaluation-report-updater');
		if (!file_exists($this->report->getFilePath())) {
			throw new AdminNotFoundException(
				sprintf(
					'Report file ‘%s’ not found',
					$this->report->getFilePath()
				)
			);
		}
	}

	// }}}

	// build phase
	// {{{ protected function buildInternal()

	protected function buildInternal()
	{
		header(
			sprintf(
				'Content-Disposition: attachment;filename="%s"',
				$this->report->filename
			)
		);

		header('Content-Type: application/pdf');

		readfile($this->report->getFilePath());

		exit();
	}

	// }}}
}

?>
