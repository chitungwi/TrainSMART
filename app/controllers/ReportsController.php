<?php
/*
* Created on Feb 27, 2008
*
*  Built for web
*  Fuse IQ -- todd@fuseiq.com
*
*/
require_once ('ReportFilterHelpers.php');
require_once ('models/table/OptionList.php');
require_once ('views/helpers/CheckBoxes.php');
require_once ('models/table/MultiAssignList.php');
require_once ('models/table/TrainingTitleOption.php');
require_once ('models/table/Helper.php');

class ReportsController extends ReportFilterHelpers {

	/**
	 * set up ZF's ContextSwitch functionality to use for generating CSV output and other output types
	 * assign CSV output type to actions that prepare data in a compatible way for this output method
	 * (see postCsvCallback())
	 * assign other output types to actions where relevant.
	 */

	public function init() {

		$contextSwitch = $this->_helper->getHelper('contextSwitch');

		$contextSwitch->addContext('csv', array(
						'headers' => array('Content-Type' => 'text/csv'),
						'callbacks' => array(
								'post' => array($this, 'postCsvCallback'),
								'init' => array($this, 'preCsvCallback')
						)
				)
		);
		$contextSwitch->addActionContext('ss-chw-statement-of-results', 'csv');
		$contextSwitch->addActionContext('ps-students-by-name', 'csv');
		$contextSwitch->addActionContext('ps-students-trained', 'csv');
        $contextSwitch->addActionContext('ps-graduated-students', 'csv');
        $contextSwitch->addActionContext('ps-repeated-students', 'csv');
		$contextSwitch->addActionContext('employee-report-occupational-category', 'csv');
        $contextSwitch->addActionContext('employees', 'csv');
        $contextSwitch->addActionContext('employees2', 'csv');//TA:#499
        $contextSwitch->addActionContext('employee-report-mechanism-transition-description', 'csv');
        $contextSwitch->addActionContext('employee-report-mechanism-transition', 'csv');
        $contextSwitch->addActionContext('employee-report-primary-role', 'csv');
        $contextSwitch->addActionContext('ps-students-licensing', 'csv'); //TA:#486
        $contextSwitch->addActionContext('ps-students-address', 'csv'); //TA:#496

		$contextSwitch->addContext('chwreport', array('suffix' => 'chwreport'));
		$contextSwitch->addActionContext('ss-chw-statement-of-results', 'chwreport');

		$contextSwitch->initContext();

	}


	public function indexAction() {

	}

	public function preDispatch() {
		$rtn = parent::preDispatch ();
		$allowActions = array ('trainingSearch' );

		if (! $this->isLoggedIn ())
		$this->doNoAccessError ();

		if (! $this->hasACL ( 'view_create_reports' ) && ! in_array ( $this->getRequest ()->getActionName (), $allowActions )) {
			$this->doNoAccessError ();
		}

		return $rtn;
	}

	public function dataAction() { 	}

	/**
	 * Converts or returns header labels. Since the export CSV must use header
	 * labels instead of database fields, define headers here.
	 *
	 * @param $fieldname = database field name to convert
	 * @param $rowRay = will add CSV headers to array
	 *
	 * @todo modify all report phtml files to use these headers
	 * @return mixed
	*/
	public function reportHeaders($fieldname = false, $rowRay = false) {

		require_once ('models/table/Translation.php');
		$translation = Translation::getAll ();

		if ($this->setting('display_mod_skillsmart')){
			$headers = array (// fieldname => label
			'id' => 'ID',
			'pcnt' => 'Participants',
			'has_known_participants' => 'Known participants',
			'region_c_name' => 'Sub-district',
			'training_method_phrase' => 'Training method',
			'is_refresher' => 'Refresher',
			'secondary_language_phrase' => 'Secondary language',
			'primary_language_phrase' => 'Primary language',
			'got_comments' => 'National curriculum comment',
			'training_got_curriculum_phrase' => 'National curriculum',
			'training_category_phrase' => 'Training category',
			'age' => 'Age',
			'comments1' => 'Professional registration number',
			'comments2' => 'Race',
			'comments3' => 'Experience',
			'score_pre' => 'Pre-test',
			'score_post' => 'Post-test',
			'score_percent_change' => '% change',
			'custom1_phrase' => 'Professional registration number',
			'city_name' => 'City',
			'cnt' => t ( 'Count' ), 'active' => @$translation ['Is Active'], 'first_name' => @$translation ['First Name'], 'middle_name' => @$translation ['Middle Name'], 'last_name' => @$translation ['Last Name'], 'training_title' => t('Training').' '.t('Name'), 'province_name' => @$translation ['Region A (Province)'], 'district_name' => @$translation ['Region B (Health District)'], 'pepfar_category_phrase' => @$translation ['PEPFAR Category'], 'training_organizer_phrase' => t('Training').' '.t('Organizer'), 'training_level_phrase' => t('Training').' '.t('Level'), 'trainer_language_phrase' => t ( 'Language' ), 'training_location_name' => t ( 'Location' ), 'training_start_date' => t ( 'Date' ), 'training_topic_phrase' => t ('Training').' '.t('topic'), 'funding_phrase' => t ( 'Funding' ), 'is_tot' => t ( 'TOT' ), 'facility_name' => t ('Facility').' '.t('name'), 'facility_type_phrase' => t ('Facility').' '.t('type'), 'facility_sponsor_phrase' => t ('Facility').' '.t('sponsor'), 'course_recommended' => t ( 'Recommended classes' ), 'recommended' => t ( 'Recommended' ), 'qualification_phrase' => t ( 'Qualification' ) . ' ' . t ( '(primary)' ), 'qualification_secondary_phrase' => t ( 'Qualification' ) . ' ' . t ( '(secondary)' ), 'gender' => t ( 'Gender' ), 'name' => t ( 'Name' ), 'email' => t ( 'Email' ), 'phone' => t ( 'Phone' ), 'cat' => t ( 'Category' ), 'language_phrase' => t ( 'Language' ), 'trainer_type_phrase' => t ( 'Type' ), 'trainer_skill_phrase' => t ( 'Skill' ), 'trainer_language_phrase' => t ( 'Language' ), 'trainer_topic_phrase' => t ( 'Topics taught' ), 'phone_work' => t ( 'Work phone' ), 'phone_home' => t ( 'Home phone' ), 'phone_mobile' => t ( 'Mobile phone' ), 'type_option_id' => 'Type' );

			// action => array(field => label)
			$headersSpecific = array ('peopleByFacility' => array ('qualification_phrase' => t ( 'Qualification' ) ), 'participantsByCategory' => array ('cnt' => t ( 'Participants' ), 'person_cnt' => t ( 'Unique participants' ) ) );
		} else {
			$headers = array (// fieldname => label
			'id' => 'ID', 
			'cnt' => t ( 'Count' ), 
			'active' => @$translation ['Is Active'], 
			'first_name' => @$translation ['First Name'], 
			'middle_name' => @$translation ['Middle Name'], 
			    'last_name' => @$translation ['Last Name'], 
			    'training_title' => t('Training').' '.t('Name'), 
			    'province_name' => @$translation ['Region A (Province)'], 
			    'district_name' => @$translation ['Region B (Health District)'], 
			    'pepfar_category_phrase' => @$translation ['PEPFAR Category'], 
			    'training_organizer_phrase' => t('Training').' '.t('Organizer'), 
			    'training_level_phrase' => t('Training Mathod'),  
			    'trainer_language_phrase' => t ( 'Language' ), 
			    'training_location_name' => t ( 'Location' ), 
			    'training_topic_phrase' => t('Topic'), 
			    'funding_phrase' => t ( 'Funding' ), 'is_tot' => t ( 'TOT' ), 
			    'facility_name' => t ('Facility').' '.t('Name'), 
			    'facility_type_phrase' => t ('Facility').' '.t('Type'), 
			    'facility_sponsor_phrase' => t ('Facility').' '.t('Sponsor'), 
			    'course_recommended' => t ( 'Recommended classes' ), 
			    'recommended' => t ( 'Recommended' ), 
			    'qualification_phrase' => t ( 'Qualification' ) . ' ' . t ( '(primary)' ), 
			    'qualification_secondary_phrase' => t ( 'Qualification' ) . ' ' . t ( '(secondary)' ), 
			    'gender' => t ( 'Gender' ), 
			    'name' => t ( 'Name' ), 
			    'email' => t ( 'Email' ), 
			    'phone' => t ( 'Phone' ), 
			    'cat' => t ( 'Category' ), 
			    'language_phrase' => t ( 'Language' ), 
			    'trainer_type_phrase' => t ( 'Type' ), 
			    'trainer_skill_phrase' => t ( 'Skill' ), 
			    'trainer_language_phrase' => t ( 'Language' ), 
			    'trainer_topic_phrase' => t ( 'Topics Taught' ), 
			    'phone_work' => t ( 'Work Phone' ), 
			    'phone_home' => t ( 'Home Phone' ), 
			    'phone_mobile' => t ( 'Mobile Phone' ), 
			    'type_option_id' => 'Type',
			    //TA:110 change titles
			    'pcnt' => 'Participants', 
			    'training_start_date' => t ( 'Start Date' ),
			    'training_end_date' => t ( 'End Date' ),
			    'training_category_phrase' => t('Program Area'),  
			    'has_known_participants' => 'Has known participants',
			 );

			// action => array(field => label)
			$headersSpecific = array ('peopleByFacility' => array ('qualification_phrase' => t ( 'Qualification' ) ), 'participantsByCategory' => array ('cnt' => t ( 'Participants' ), 'person_cnt' => t ( 'Unique Participants' ) ) );
		}
        if ($rowRay) {
			$keys = array_keys ( reset ( $rowRay ) );
			foreach ( $keys as $k ) {
				$csvheaders [] = $this->reportHeaders ( $k );
			}

			return array_merge ( array ('csvheaders' => $csvheaders ), $rowRay );

		} elseif ($fieldname) {

			// check report specific headers first
			$action = $this->getRequest ()->getActionName ();
			if (isset ( $headersSpecific [$action] ) && isset ( $headersSpecific [$action] [$fieldname] )) {
				return $headersSpecific [$action] [$fieldname];
			}

			return (isset ( $headers [$fieldname] )) ? $headers [$fieldname] : $fieldname;
		} else {
			return $headers;
		}

	}

	public function compcsvAction() {
		$v1=explode("~",$this->getSanParam ( 'v1' ));
		$v2=explode("~",$this->getSanParam ( 'v2' ));
		$p=$this->getSanParam ( 'p' );
		$d=$this->getSanParam ( 'd' );
		$s=$this->getSanParam ( 's' );
		$f=$this->getSanParam ( 'f' );
		$this->viewAssignEscaped ( 'v1', $v1 );
		$this->viewAssignEscaped ( 'v2', $v2 );
		$this->viewAssignEscaped ( 'p',  $p);
		$this->viewAssignEscaped ( 'd',  $d);
		$this->viewAssignEscaped ( 's',  $s);
		$this->viewAssignEscaped ( 'f',  $f);
	}

	public function profcsvAction() {
		$v1=explode("~",$this->getSanParam ( 'v1' ));
		$v2=explode("~",$this->getSanParam ( 'v2' ));
		$v3=explode("~",$this->getSanParam ( 'v3' ));
		$v4=explode("~",$this->getSanParam ( 'v4' ));
		$v5=explode("~",$this->getSanParam ( 'v5' ));
		$v6=explode("~",$this->getSanParam ( 'v6' ));
		$p=$this->getSanParam ( 'p' );
		$d=$this->getSanParam ( 'd' );
		$s=$this->getSanParam ( 's' );
		$f=$this->getSanParam ( 'f' );
		$this->viewAssignEscaped ( 'v1', $v1 );
		$this->viewAssignEscaped ( 'v2', $v2 );
		$this->viewAssignEscaped ( 'v3', $v3 );
		$this->viewAssignEscaped ( 'v4', $v4 );
		$this->viewAssignEscaped ( 'v5', $v5 );
		$this->viewAssignEscaped ( 'v6', $v6 );
		$this->viewAssignEscaped ( 'p',  $p);
		$this->viewAssignEscaped ( 'd',  $d);
		$this->viewAssignEscaped ( 's',  $s);
		$this->viewAssignEscaped ( 'f',  $f);
	}

	public function detailAction() {
		
		$helper = new Helper();
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['training_title_option_id'] = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['ques'] = $this->getSanParam ( 'ques' );
		$criteria ['score_id'] = $this->getSanParam ( 'score_id' );
		$criteria ['primarypatients'] = $this->getSanParam ( 'primarypatients' );
		$criteria ['hivInput'] = $this->getSanParam ( 'hivInput' );
		$criteria ['trainer_type_option_id1'] = $this->getSanParam ( 'trainer_type_option_id1' );
		$criteria ['grp1'] = $this->getSanParam ( 'grp1' );
		$criteria ['grp2'] = $this->getSanParam ( 'grp2' );
		$criteria ['grp3'] = $this->getSanParam ( 'grp3' );
		$criteria ['go'] = $this->getSanParam ( 'go' );

		$complist = $helper->getQualificationCompetencies();

		if ($criteria ['go']) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
			$sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp';
			if ( $criteria['training_title_option_id'] ) {
				$sql .= ', person_to_training as ptt ';
				$sql .= ', training as tr  ';
			}
			$where = array('p.is_deleted = 0');
			$whr = array();
			$where []= 'cmp.person = p.id';
			if ($criteria ['facilityInput']) {
				$where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
			}
			if ( $criteria['training_title_option_id'] ) {
				$where []= ' p.id = ptt.person_id AND ptt.training_id = tr.id AND tr.training_title_option_id = ' . ($criteria ['training_title_option_id']) . ' ';
			}
			if( isset($criteria ['qualification_id']) && $criteria ['qualification_id'] != ''){
	 			$where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option WHERE parent_id = ' . $criteria ['qualification_id'] . ') ';
			}
			$where []= 'cmp.active = \'Y\'';

			// GETTING QUESTIONS TIED TO THE SELECTED COMPETENCIES
			$questionids = $helper->getCompQuestions($this->getSanParam ( 'complist' ));

			$whr []= 'cmp.question IN ('."'".str_replace(",","','", implode(",", $questionids)) ."'".')';

			if( !empty($where) ){ $sql .= ' WHERE ' . implode(' AND ', $where); }
			if( !empty($whr) ){ $sql .= ' AND (' . implode(' OR ', $whr) . ')'; }


			$return = array();
			// For each competency, we loop through this block
			foreach ($this->getSanParam('complist') as $cid){
				// Getting competency details
				$thiscomp = $helper->getSkillSmartCompetencies($cid);

				// Getting ids for questions that are in this competency
				$curids = $helper->getCompQuestions(array($cid));

				$count = 0;
				$total = 0;
				foreach ( $rowArray as $k => $v ) {
					// Check if the question belongs to this competency
					if (in_array($v['question'], $curids)){
						switch (strtoupper($v['option'])){
							case "A":
								$total += 4;
								$count++;
							break;
							case "B":
								$total += 3;
								$count++;
							break;
							case "C":
								$total += 2;
								$count++;
							break;
							case "D":
								$total += 1;
								$count++;
							break;
						}
					}
				}
				if ($count > 0){
					$total = number_format((($total/(4*$count))*100),2);
				}
				$return[$thiscomp['label']] = $total;
			}
			$this->viewAssignEscaped("reportoutput",$return);

// TODO: WTF?
die ("OK");
			$rowArray = $db->fetchAll ( $sql );
			$qss=array();
			$nmss=array();
		}
		$this->view->assign ( 'criteria', $criteria );
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		$this->viewAssignEscaped ( 'complist', $complist );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
	}

	public function compAction() {
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['ques'] = $this->getSanParam ( 'ques' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		
		//TA:29 fixing bug
		$helper = new Helper();
		$complist = $helper->getQualificationCompetencies();
		
		if ($criteria ['go']) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
			$sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp '; //compres as cmpr';
			$where = array('p.is_deleted = 0');
			$whr = array();
			//TA:29 fix bug $where []= 'cmpr.person = p.id';
			$where []= 'cmp.person = p.id';
			$where []= ' p.primary_qualification_option_id = q.id and p.facility_id = f.id and f.location_id = l.id ';
			if ($criteria ['facilityInput']) {
				$where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
			}
			//TA:29 fix bug, why should we take by parent_id????
			$where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option) ';
			$where []= 'cmp.active = \'Y\'';
			
			//TA:29 fixing bug
			$questionids = $helper->getCompQuestions($this->getSanParam ( 'complist' ));
			$whr []= 'cmp.question IN ('."'".str_replace(",","','", implode(",", $questionids)) ."'".')';
			

			$sql .= ' WHERE ' . implode(' AND ', $where);
			if(!empty($whr)){ //TA:29 do not add if array is empty
				$sql .= ' AND (' . implode(' OR ', $whr) . ')';
			}

			
			$rowArray = $db->fetchAll ( $sql);

			$qss = $this->getSanParam ( 'complist' ); 
			$nmss=explode("~",$this->getSanParam ( 'listpq' ));
			
			//TA:29 fix bug
			$ct=0;
			$rss=array();
			foreach ( $qss as $kys => $vls ) {	
				$thiscomp = $helper->getSkillSmartCompetencies($vls);
				$ct = $thiscomp['label'];
				$rss[$ct]=0;
				$ctt=0;
				//TA:29
				$wss=explode(",",$nmss[$kys]);
				foreach ( $wss as $kyss => $vlss ) {
					foreach ( $rowArray as $kss => $vss ) {
						if($vlss." " == $vss['question']." "){
							if($vss['option']=="A"){
								$rss[$ct]=$rss[$ct]+4;
							}else{
								if($vss['option']=="B"){
									$rss[$ct]=$rss[$ct]+3;
								}else{
									if($vss['option']=="C"){
										$rss[$ct]=$rss[$ct]+2;
									}else{
										if($vss['option']=="D"){
											$rss[$ct]=$rss[$ct]+1;
										}
									}
								}
							}
							$ctt=$ctt+1;
						}
					}
				}
				if($ctt>0){
				 $rss[$ct]=number_format((($rss[$ct]/(4*$ctt))*100),2);
				}
				//$ct=$ct+1;//TA:29 fix bug
			}
			
			$this->viewAssignEscaped ( 'results', $rowArray );
			$this->viewAssignEscaped ( 'rss', $rss );
		}
		$this->view->assign ( 'criteria', $criteria );
		
		//TA:29 fixing bug
		$this->viewAssignEscaped ( 'complist', $complist );
		
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
	}


	public function profAction() {
		$helper = new Helper();
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['training_title_option_id'] = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['ques'] = $this->getSanParam ( 'ques' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		$criteria ['all'] = $this->getSanParam ( 'all' );

		$complist = $helper->getQualificationCompetencies();

		if ($criteria ['go']) {
			if ($criteria ['all']) {
				$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
				$num_locs = $this->setting('num_location_tiers');
				list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
				$sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp '; //TA:30 fix bug , compres as cmpr';
				if ( $criteria['training_title_option_id'] ) {
					$sql .= ', person_to_training as ptt ';
					$sql .= ', training as tr  ';
				}
				$where = array('p.is_deleted = 0');
				//TA:30 fix bug $where []= 'cmpr.person = p.id';
				$where []= 'cmp.person = p.id';
				$where []= ' p.primary_qualification_option_id = q.id and p.facility_id = f.id and f.location_id = l.id ';
				if ($criteria ['facilityInput']) {
					$where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
				}
				if ( $criteria['training_title_option_id'] ) {
					$where []= ' p.id = ptt.person_id AND ptt.training_id = tr.id AND tr.training_title_option_id = ' . ($criteria ['training_title_option_id']) . ' ';
				}
				$where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option WHERE parent_id IN (6, 7, 8, 9) ) ';
				//TA:30 fix bug $where []= 'cmpr.active = \'Y\'';
				//TA:30 fix bug $where []= 'cmpr.res = 1';
				$where []= 'cmp.active = \'Y\'';
				$sql .= ' WHERE ' . implode(' AND ', $where);

echo $sql . "<br>";

				$rowArray = $db->fetchAll ( $sql );
				$qss=array();
				$nmss=array();
				$qss=explode(",","0,1,2,3,4,5,6,7");
				$nmss=explode("~","1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,200~01,02,03,04,05,06,07,08,09~31,32,33,34,35,36,37,38~41,42,43,44,45~51,52,53,54,55,56,57,58,59,510,511,512,513,514,515,516,517,518~61,62,63,64,65,66,67~71,72,73,74,75,76,77,78,79,710,711~21,22,23");

				$ct=0;
				$rssA=array();
				$rssB=array();
				$rssC=array();
				$rssD=array();
				$rssE=array();
				foreach ( $qss as $kys => $vls ) {
					$rssA[$ct]=0;
					$rssB[$ct]=0;
					$rssC[$ct]=0;
					$rssD[$ct]=0;
					$rssE[$ct]=0;
					$ctt=0;
					$wss=explode(",",$nmss[$vls]);
					foreach ( $wss as $kyss => $vlss ) {
						foreach ( $rowArray as $kss => $vss ) {
							if($vlss." " == $vss['question']." ")
							{
								if($vss['option']=="A")
								{
									$rssA[$ct]=$rssA[$ct]+1;
								}
								else
								{
									if($vss['option']=="B")
									{
										$rssB[$ct]=$rssB[$ct]+1;
									}
									else
									{
										if($vss['option']=="C")
										{
											$rssC[$ct]=$rssC[$ct]+1;
										}
										else
										{
											if($vss['option']=="D")
											{
												$rssD[$ct]=$rssD[$ct]+1;
											}
											else
											{
												if($vss['option']=="E")
												{
													$rssE[$ct]=$rssE[$ct]+1;
												}
											}
										}
									}
								}
								$ctt=$ctt+1;
							}
						}
					}
					if($ctt>0) {
						$rssA[$ct]=number_format((($rssA[$ct]/$ctt)*100),2);
						$rssB[$ct]=number_format((($rssB[$ct]/$ctt)*100),2);
						$rssC[$ct]=number_format((($rssC[$ct]/$ctt)*100),2);
						$rssD[$ct]=number_format((($rssD[$ct]/$ctt)*100),2);
						$rssE[$ct]=number_format((($rssE[$ct]/$ctt)*100),2);
					}
					$ct=$ct+1;
				}
				$this->viewAssignEscaped ( 'results', $rowArray );
				$this->viewAssignEscaped ( 'rssA', $rssA );
				$this->viewAssignEscaped ( 'rssB', $rssB );
				$this->viewAssignEscaped ( 'rssC', $rssC );
				$this->viewAssignEscaped ( 'rssD', $rssD );
				$this->viewAssignEscaped ( 'rssE', $rssE );
			}
			else
			{
				$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
				$num_locs = $this->setting('num_location_tiers');
				list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
				$sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp'; ////TA:30 fix bug, compres as cmpr';
				if ( $criteria['training_title_option_id'] ) {
					$sql .= ', person_to_training as ptt ';
					$sql .= ', training as tr  ';
				}
				$where = array('p.is_deleted = 0');
				$whr = array();
				//TA:30 fix bug $where []= 'cmpr.person = p.id';
				$where []= 'cmp.person = p.id';
				$where []= ' p.primary_qualification_option_id = q.id and p.facility_id = f.id and f.location_id = l.id ';
				if ($criteria ['facilityInput']) {
					$where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
				}
				if ( $criteria['training_title_option_id'] ) {
					$where []= ' p.id = ptt.person_id AND ptt.training_id = tr.id AND tr.training_title_option_id = ' . ($criteria ['training_title_option_id']) . ' ';
				}
				//TA:30 fix bug
				//$where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option WHERE parent_id = ' . $criteria ['qualification_id'] . ') ';
				$where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option) ';
				//TA:30 fix bug $where []= 'cmpr.active = \'Y\'';
				//TA:30 fix bug $where []= 'cmpr.res = 1';
				$where []= 'cmp.active = \'Y\'';


				// GETTING QUESTIONS TIED TO THE SELECTED COMPETENCIES
				$questionids = $helper->getCompQuestions($this->getSanParam ( 'complist' ));

				$whr []= 'cmp.question IN ('."'".str_replace(",","','", implode(",", $questionids)) ."'".')';


				if( !empty($where) ){ $sql .= ' WHERE ' . implode(' AND ', $where); }
				if( !empty($whr) ){ $sql .= ' AND (' . implode(' OR ', $whr) . ')'; }
				
				$rowArray = $db->fetchAll ($sql );

				$return = array();
				// For each competency, we loop through this block
				foreach ($this->getSanParam('complist') as $cid){
					// Getting competency details
					$thiscomp = $helper->getSkillSmartCompetencies($cid);

					// Getting ids for questions that are in this competency
					$curids = $helper->getCompQuestions(array($cid));

					$count = 0;
					$totala = 0;
					$totalb = 0;
					$totalc = 0;
					$totald = 0;
					$totale = 0;
					foreach ( $rowArray as $k => $v ) {
						// Check if the question belongs to this competency
						if (in_array($v['question'], $curids)){
							switch (strtoupper($v['option'])){
								case "A":
									$totala++;
									$count++;
								break;
								case "B":
									$totalb++;
									$count++;
								break;
								case "C":
									$totalc++;
									$count++;
								break;
								case "D":
									$totald++;
									$count++;
								break;
								case "D":
									$totale++;
									$count++;
								break;
							}
						}
					}
					if ($count > 0){
						number_format((($rssA[$ct]/$ctt)*100),2);
						$return[$thiscomp['label']] = array(
							"A" => number_format((($totala / $count) * 100), 2),
							"B" => number_format((($totalb / $count) * 100), 2),
							"C" => number_format((($totalc / $count) * 100), 2),
							"D" => number_format((($totald / $count) * 100), 2),
							"E" => number_format((($totale / $count) * 100), 2),
						);
					} else {
						$return[$thiscomp['label']] = array(
							"A" => 0,
							"B" => 0,
							"C" => 0,
							"D" => 0,
							"E" => 0,
						);
					}
				}
				$this->viewAssignEscaped("reportoutput",$return);



				$qss=array();
				$nmss=array();
				if($criteria ['qualification_id']=="6")
				{
					$qss=explode(",",$this->getSanParam ( 'ques' ));
					$nmss=explode("~",$this->getSanParam ( 'listcq' ));
				}
				if($criteria ['qualification_id']=="7")
				{
					$qss=explode(",",$this->getSanParam ( 'ques' ));
					$nmss=explode("~",$this->getSanParam ( 'listdq' ));
				}
				if($criteria ['qualification_id']=="8")
				{
					$qss=explode(",",$this->getSanParam ( 'ques' ));
					$nmss=explode("~",$this->getSanParam ( 'listnq' ));
				}
				if($criteria ['qualification_id']=="9")
				{
					$qss=explode(",",$this->getSanParam ( 'ques' ));
					$nmss=explode("~",$this->getSanParam ( 'listpq' ));
				}
				$ct;
				$ct=0;
				$rssA=array();
				$rssB=array();
				$rssC=array();
				$rssD=array();
				$rssE=array();
				$ctt;
				foreach ( $qss as $kys => $vls ) {
					$rssA[$ct]=0;
					$rssB[$ct]=0;
					$rssC[$ct]=0;
					$rssD[$ct]=0;
					$rssE[$ct]=0;
					$ctt=0;
					$wss=explode(",",$nmss[$vls]);
					foreach ( $wss as $kyss => $vlss ) {
						foreach ( $rowArray as $kss => $vss ) {
							if($vlss." " == $vss['question']." ")
							{
								if($vss['option']=="A")
								{
									$rssA[$ct]=$rssA[$ct]+1;
								}
								else
								{
									if($vss['option']=="B")
									{
										$rssB[$ct]=$rssB[$ct]+1;
									}
									else
									{
										if($vss['option']=="C")
										{
											$rssC[$ct]=$rssC[$ct]+1;
										}
										else
										{
											if($vss['option']=="D")
											{
												$rssD[$ct]=$rssD[$ct]+1;
											}
											else
											{
												if($vss['option']=="E")
												{
													$rssE[$ct]=$rssE[$ct]+1;
												}
											}
										}
									}
								}
								$ctt=$ctt+1;
							}
						}
					}
					if($ctt>0) {
						$rssA[$ct]=number_format((($rssA[$ct]/$ctt)*100),2);
						$rssB[$ct]=number_format((($rssB[$ct]/$ctt)*100),2);
						$rssC[$ct]=number_format((($rssC[$ct]/$ctt)*100),2);
						$rssD[$ct]=number_format((($rssD[$ct]/$ctt)*100),2);
						$rssE[$ct]=number_format((($rssE[$ct]/$ctt)*100),2);
					}
					$ct=$ct+1;
				}
				$this->viewAssignEscaped ( 'results', $rowArray );
				$this->viewAssignEscaped ( 'rssA', $rssA );
				$this->viewAssignEscaped ( 'rssB', $rssB );
				$this->viewAssignEscaped ( 'rssC', $rssC );
				$this->viewAssignEscaped ( 'rssD', $rssD );
				$this->viewAssignEscaped ( 'rssE', $rssE );
			}
		}
		$this->view->assign ( 'criteria', $criteria );
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		require_once ('models/table/TrainingTitleOption.php');
		$titleArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $titleArray );
		$this->viewAssignEscaped ( 'complist', $complist );
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
	}

	public function compcompAction() {
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['Questions'] = $this->getSanParam ( 'Questions' );
		$criteria ['outputType'] = $this->getSanParam ( 'outputType' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		
		//TA:31 fixing bug
		$helper = new Helper();
		$complist = $helper->getQualificationCompetencies();
		
		if ($criteria ['go']) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$prsns=array();
			$prsnscnt=0;

			//TA:31 fixing bug, by some reason it was not taken for any qualification, let's do it
			$sql='SELECT `person`, SUM(-(ASCII(`option`)-69)) `sm` FROM `comp`';
			$whr = array();
			$whr []= '`question` IN ('."'".str_replace(",","','",$this->getSanParam ( 'listpq' ))."'".')';
			$sql .= ' WHERE `active` = \'Y\' AND `option` <> \'E\' AND `option` <> \'F\' AND (' . implode(' OR ', $whr) . ')';
			$sql .= ' GROUP BY `person`';
			
			$rowArray = $db->fetchAll ( $sql );
			$tlques=explode(",",$this->getSanParam ( 'listpq' ));
			$ttlques=count($tlques);
			$qs=$this->getSanParam ( 'score_id' );
			foreach ( $qs as $kys => $vls ) {
				$fr=$vls;
				$min=0;
				$max=0;
				if($fr =="100"){
					$min=90;
					$max=100;
				}else{
					if($fr =="89"){
						$min=75;
						$max=90;
					}else{
						if($fr =="74"){
							$min=60;
							$max=75;
						}else{
							$min=1;
							$max=60;
						}
					}
				}
				foreach ( $rowArray as $prsn => $mrk ) {
					$prcnt=number_format((($mrk['sm']/(4*$ttlques))*100),2);
					if($prcnt>$min && $prcnt<=$max){
						$prsns[$prsnscnt]=$mrk['person'];
						$prsnscnt=$prsnscnt+1;
					}
				}
			}
			//TA:31 end
			
			$num_locs = $this->setting('num_location_tiers');
			
			list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria); //TA:26 fixing bug, do not move this line from here
			
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
			//TA:31 fixing bug $sql = 'SELECT  DISTINCT p.`id`, p.`first_name` ,  p.`last_name` ,  p.`gender` FROM `person` as p, facility as f, ('.$location_sub_query.') as l, `person_qualification_option` as q WHERE p.`primary_qualification_option_id` = q.`id` and p.facility_id = f.id and f.location_id = l.id AND p.`primary_qualification_option_id` IN (SELECT `id` FROM `person_qualification_option` WHERE `parent_id` = ' . $criteria ['qualification_id'] . ') AND p.`is_deleted` = 0 AND p.`id` IN (';
			$sql = 'SELECT  DISTINCT p.`id`, p.`first_name` ,  p.`last_name` ,  p.`gender` FROM `person` as p, facility as f, ('.$location_sub_query.') as l, `person_qualification_option` as q WHERE p.`primary_qualification_option_id` = q.`id` and p.facility_id = f.id and f.location_id = l.id AND p.`primary_qualification_option_id` IN (SELECT `id` FROM `person_qualification_option` WHERE p.primary_qualification_option_id = ' . $criteria ['qualification_id'] . ') AND p.`is_deleted` = 0 AND p.`id` IN (';
			if(count($prsns)>0){
				foreach ( $prsns as $k => $v ) {
					$sql = $sql . $v . ',';
				}
			}
			$sql = $sql . '0';
			if ($criteria ['facilityInput']) {
				$sql = $sql . ') AND p.facility_id = "' . $criteria ['facilityInput'] . '";';
			}
			else {
				$sql = $sql . ');';
			}
			$rowArray = $db->fetchAll ( $sql );
			if ($criteria ['outputType']) {
				$this->sendData ( $this->reportHeaders ( false, $rowArray ) );
			}
			$this->viewAssignEscaped ( 'results', $rowArray );
		}
		$this->view->assign ( 'criteria', $criteria );
		
		//TA:31 fixing bug
		$this->viewAssignEscaped ( 'complist', $complist );
		
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
	}

	public function quescompAction() {
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['Questions'] = $this->getSanParam ( 'Questions' );
		$criteria ['outputType'] = $this->getSanParam ( 'outputType' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		
		//TA:31 fixing bug
		$helper = new Helper();
		$complist = $helper->getQualificationCompetencies();
		
		if ($criteria ['go']) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			
			//TA:32 fixing bug, add this part
			$prsns=array();
			$prsnscnt=0;
			$sql='SELECT `person`, SUM(-(ASCII(`option`)-69)) `sm` FROM `comp`';
			$whr = array();
			$whr []= '`question` IN ('."'".str_replace(",","','",$this->getSanParam ( 'listpq' ))."'".')';
			$sql .= ' WHERE `active` = \'Y\' AND `option` <> \'E\' AND `option` <> \'F\' AND (' . implode(' OR ', $whr) . ')';
			
			//TA:32 ADD questions  to sql query, take persons who answered for question 'A'
			if($this->getSanParam ( 'quetion' )){
				$sql .= ' AND `option` in (\'' . implode('\',\'', $this->getSanParam ( 'quetion' )) . '\')';
			}
			
			$sql .= ' GROUP BY `person`';
			
			$rowArray = $db->fetchAll ( $sql );
			$tlques=explode(",",$this->getSanParam ( 'listpq' ));
			$ttlques=count($tlques);
			$qs=$this->getSanParam ( 'score_id' );
			foreach ( $qs as $kys => $vls ) {
				$fr=$vls;
				$min=0;
				$max=0;
				if($fr =="100"){
					$min=90;
					$max=100;
				}else{
					if($fr =="89"){
						$min=75;
						$max=90;
					}else{
						if($fr =="74"){
							$min=60;
							$max=75;
						}else{
							$min=1;
							$max=60;
						}
					}
				}
				foreach ( $rowArray as $prsn => $mrk ) {
					$prcnt=number_format((($mrk['sm']/(4*$ttlques))*100),2);
					if($prcnt>$min && $prcnt<=$max){
						$prsns[$prsnscnt]=$mrk['person'];
						$prsnscnt=$prsnscnt+1;
					}
				}
			}
			//TA:32 end
			
			//TA:32 fixing bug
			$sql='SELECT `person` FROM `comp`';
			$sql .= ' WHERE `active` = \'Y\'';
			$whr = array();
			foreach ( $qs as $k => $v ) {
				$qss=explode('^',$v);
				$whr[]='(`question`=\''.$qss[2].'\' AND `option`=\''.$qss[3].'\')';
			}
			if( !empty($whr) )
				$sql .= ' AND (' . implode(' OR ', $whr) . ')';

			$rowArray = $db->fetchAll ( $sql );
			//TA:32 fixing bug
			//$sql = 'SELECT  DISTINCT p.`id`, p.`first_name` ,  p.`last_name` ,  p.`gender` FROM `person` as p, `person_qualification_option` as q WHERE p.`primary_qualification_option_id` = q.`id` AND p.`primary_qualification_option_id` IN (SELECT `id` FROM `person_qualification_option` WHERE `parent_id` = ' . $criteria ['qualification_id'] . ') AND p.`is_deleted` = 0 AND p.`id` IN (';
			$sql = 'SELECT  DISTINCT p.`id`, p.`first_name` ,  p.`last_name` ,  p.`gender` FROM `person` as p, `person_qualification_option` as q WHERE p.`primary_qualification_option_id` = q.`id` AND p.`primary_qualification_option_id` IN (SELECT `id` FROM `person_qualification_option` WHERE p.primary_qualification_option_id = ' . $criteria ['qualification_id'] . ') AND p.`is_deleted` = 0 AND p.`id` IN (';
			if(count($prsns)>0){
				foreach ( $prsns as $k => $v ) {
					$sql = $sql . $v . ',';
				}
			}
			//end
			$sql = $sql . '0);';
			
			$rowArray = $db->fetchAll ( $sql );
			if ($criteria ['outputType']) {
				$this->sendData ( $this->reportHeaders ( false, $rowArray ) );
			}
			$this->viewAssignEscaped ( 'results', $rowArray );
		}
		$this->view->assign ( 'criteria', $criteria );
		
		//TA:32 fixing bug
		$this->viewAssignEscaped ( 'complist', $complist );
		
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
	}

	public function trainingsAction() {
		$this->view->assign ( 'mode', 'name' );

		return $this->trainingReport ();
	}

	public function trainingSearchAction() {
		$this->_countrySettings = array();
		$this->_countrySettings = System::getAll();

		$this->view->assign ( 'mode', 'search' );

		return $this->trainingReport ();
	}

	public function trainingByParticipantsAction() {
		$this->view->assign ( 'mode', 'count' );

		return $this->trainingReport ();
	}
	
	//TA:SA-DLT
// 	public function trainingByParticipantcountAction(){
	    
// 	    $this->view->assign ( 'mode', 'count' );
// 	    $this->_countrySettings = array();
// 	    $this->_countrySettings = System::getAll();
	    
// 	    require_once ('models/table/TrainingLocation.php');
// 	    require_once('views/helpers/TrainingViewHelper.php');
	    
// 	    $criteria = array ();
// 	    $where = array ();
// 	    $display_training_partner = ( isset($this->_countrySettings['display_training_partner']) && $this->_countrySettings['display_training_partner'] == 1 ) ? true : false;
	    
// 	    //find the first date in the database
// 	    $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
// 	    $sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
// 	    $rowArray = $db->fetchAll ( $sql );
// 	    $start_default = $rowArray [0] ['start'];
// 	    $parts = explode('-', $start_default );
// 	    $criteria ['start-year'] = @$parts [0];
// 	    $criteria ['start-month'] = @$parts [1];
// 	    $criteria ['start-day'] = @$parts [2];
	    
// 	    if ($this->getSanParam ( 'start-year' ))
// 	        $criteria ['start-year'] = $this->getSanParam ( 'start-year' );
// 	        if ($this->getSanParam ( 'start-month' ))
// 	            $criteria ['start-month'] = $this->getSanParam ( 'start-month' );
// 	            if ($this->getSanParam ( 'start-day' ))
// 	                $criteria ['start-day'] = $this->getSanParam ( 'start-day' );
// 	                if ($this->view->mode == 'search') {
// 	                    $sql = "SELECT MAX(training_start_date) as \"start\" FROM training ";
// 	                    $rowArray = $db->fetchAll ( $sql );
// 	                    $end_default = $rowArray [0] ['start'];
// 	                    $parts = explode('-', $end_default );
// 	                    $criteria ['end-year'] = @$parts [0];
// 	                    $criteria ['end-month'] = @$parts [1];
// 	                    $criteria ['end-day'] = @$parts [2];
// 	                } else {
// 	                    $criteria ['end-year'] = date ( 'Y' );
// 	                    $criteria ['end-month'] = date ( 'm' );
// 	                    $criteria ['end-day'] = date ( 'd' );
// 	                }
	                
// 	                if ($this->getSanParam ( 'end-year' ))
// 	                    $criteria ['end-year'] = $this->getSanParam ( 'end-year' );
// 	                    if ($this->getSanParam ( 'end-month' ))
// 	                        $criteria ['end-month'] = $this->getSanParam ( 'end-month' );
// 	                        if ($this->getSanParam ( 'end-day' ))
// 	                            $criteria ['end-day'] = $this->getSanParam ( 'end-day' );
	                            
// 	                            // find training name from new category/title format: categoryid_titleid
// 	                            $ct_ids = $criteria ['training_category_and_title_id'] = $this->getSanParam ( 'training_category_and_title_id' );
// 	                            $criteria ['training_title_option_id'] = $this->_pop_all($ct_ids);
// 	                            $criteria ['training_title'] = $this->getSanParam ( 'training_title' );
// 	                            $criteria ['training_location_id'] =                     $this->getSanParam ( 'training_location_id' );
// 	                            $criteria ['training_organizer_id'] =                    $this->getSanParam ( 'training_organizer_id' );
// 	                            $criteria ['training_pepfar_id'] =                       $this->getSanParam ( 'training_pepfar_id' );
// 	                            $criteria ['training_method_id'] =                       $this->getSanParam ( 'training_method_id' );
// 	                            $criteria ['mechanism_id'] =                             $this->getSanParam ( 'mechanism_id' );
// 	                            $criteria ['training_topic_id'] =                        $this->getSanParam ( 'training_topic_id' );
// 	                            $criteria ['training_level_id'] =                        $this->getSanParam ( 'training_level_id' );
// 	                            $criteria ['training_primary_language_option_id'] =      $this->getSanParam ( 'training_primary_language_option_id' );
// 	                            $criteria ['training_secondary_language_option_id'] =    $this->getSanParam ( 'training_secondary_language_option_id' );
// 	                            $criteria ['training_category_id'] =                     $this->getSanParam ( 'training_category_id' ); //reset(explode('_',$ct_ids));//
// 	                            $criteria ['training_got_curric_id'] =                   $this->getSanParam ( 'training_got_curric_id' );
// 	                            $criteria ['is_tot'] =                                   $this->getSanParam ( 'is_tot' );
// 	                            $criteria ['funding_id'] =                               $this->getSanParam ( 'funding_id' );
// 	                            $criteria ['custom_1_id'] =                              $this->getSanParam ( 'custom_1_id' );
// 	                            $criteria ['custom_2_id'] =                              $this->getSanParam ( 'custom_2_id' );
// 	                            $criteria ['custom_3_id'] =                              $this->getSanParam ( 'custom_3_id' );
// 	                            $criteria ['custom_4_id'] =                              $this->getSanParam ( 'custom_4_id' );
// 	                            $criteria ['created_by'] =                               $this->getSanParam ( 'created_by' );
// 	                            $criteria ['creation_dates'] =                           $this->getSanParam ( 'creation_dates' );
// 	                            $criteria ['funding_min'] =                              $this->getSanParam ( 'funding_min' );
// 	                            $criteria ['funding_max'] =                              $this->getSanParam ( 'funding_max' );
// 	                            $criteria ['refresher_id'] =                             $this->getSanParam ( 'refresher_id' );
// 	                            $criteria ['person_to_training_viewing_loc_option_id'] = $this->getSanParam('person_to_training_viewing_loc_option_id');
// 	                            $criteria ['primary_responsibility_option_id'] =         $this->getSanParam ( 'primary_responsibility_option_id' );
// 	                            $criteria ['secondary_responsibility_option_id'] =       $this->getSanParam ( 'secondary_responsibility_option_id' );
// 	                            $criteria ['highest_edu_level_option_id'] =              $this->getSanParam ( 'highest_edu_level_option_id' );
// 	                            $criteria ['qualification_id'] =                         $this->getSanParam ( 'qualification_id' );
// 	                            $criteria ['qualification_secondary_id'] =               $this->getSanParam ( 'qualification_secondary_id' );
// 	                            $criteria ['doCount'] =       ($this->view->mode == 'count');
// 	                            $criteria ['doName'] =       ($this->view->mode == 'name');
	                            
// 	                            if($criteria['doCount'] || $criteria ['doName']) {
// 	                                $criteria ['age_max'] =                                $this->getSanParam ( 'age_max' );
// 	                                $criteria ['age_min'] =                                $this->getSanParam ( 'age_min' );
// 	                                $criteria ['training_gender'] =                       $this->getSanParam ( 'training_gender' );
// 	                            }
	                            
// 	                            //TA:26 fix bug, get http parameter
// 	                            $criteria ['province_id'] = $this->getSanParam ( 'province_id' );
// 	                            $arr_dist = $this->getSanParam ( 'district_id' );
// 	                            // level 2 location has parameter as [parent_location_id]_[location_id], we need to take only location_ids
// 	                            for($i=0;$i<sizeof($arr_dist); $i++){
// 	                                if ( strstr($arr_dist[$i], '_') !== false ) {
// 	                                    $parts = explode('_',$arr_dist[$i]);
// 	                                    $arr_dist[$i] = $parts[1];
// 	                                }
// 	                            }
// 	                            $criteria ['district_id'] = $arr_dist;
	                            
// 	                            $criteria ['go'] = $this->getSanParam ( 'go' );
// 	                            $criteria ['showProvince'] =  'on';
// 	                            $criteria ['showDistrict'] =  'on';
// 	                            $criteria ['showRegionC'] =   'on';
// 	                            $criteria ['showRegionD'] =   ($this->getSanParam ( 'showRegionD' ) or ($criteria ['doCount'] and ($criteria ['region_d_id'] or ! empty ( $criteria ['region_d_id'] ))));
// 	                            $criteria ['showRegionE'] =   ($this->getSanParam ( 'showRegionE' ) or ($criteria ['doCount'] and ($criteria ['region_e_id'] or ! empty ( $criteria ['region_e_id'] ))));
// 	                            $criteria ['showRegionF'] =   ($this->getSanParam ( 'showRegionF' ) or ($criteria ['doCount'] and ($criteria ['region_f_id'] or ! empty ( $criteria ['region_f_id'] ))));
// 	                            $criteria ['showRegionG'] =   ($this->getSanParam ( 'showRegionG' ) or ($criteria ['doCount'] and ($criteria ['region_g_id'] or ! empty ( $criteria ['region_g_id'] ))));
// 	                            $criteria ['showRegionH'] =   ($this->getSanParam ( 'showRegionH' ) or ($criteria ['doCount'] and ($criteria ['region_h_id'] or ! empty ( $criteria ['region_h_id'] ))));
// 	                            $criteria ['showRegionI'] =   ($this->getSanParam ( 'showRegionI' ) or ($criteria ['doCount'] and ($criteria ['region_i_id'] or ! empty ( $criteria ['region_i_id'] ))));
// 	                            $criteria ['showTrainingTitle'] = 'on';
// 	                            $criteria ['showTrainingStartDate'] = 'on';
// 	                            $criteria ['showLocation'] =  'on';
// 	                            $criteria ['showOrganizer'] = ($this->getSanParam ( 'showOrganizer' ) or ($criteria ['doCount'] and ($criteria ['training_organizer_id'])));
// 	                            $criteria ['showMechanism'] = ($this->getSanParam ( 'showMechanism' ) or ($criteria ['doCount'] and $criteria ['mechanism_id']));
// 	                            $criteria ['showPepfar'] =    ($this->getSanParam ( 'showPepfar' ) or ($criteria ['doCount'] and ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0')));
// 	                            $criteria ['showMethod'] =    ($this->getSanParam ( 'showMethod' ) or ($criteria ['doCount'] and ($criteria ['training_method_id'] or $criteria ['training_method_id'] === '0')));
// 	                            $criteria ['showTopic'] =     ($this->getSanParam ( 'showTopic' ) or ($criteria ['doCount'] and ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')));
// 	                            $criteria ['showLevel'] =     ($this->getSanParam ( 'showLevel' ) or ($criteria ['doCount'] and $criteria ['training_level_id']));
// 	                            $criteria ['showTot'] =       ($this->getSanParam ( 'showTot' ) or ($criteria ['doCount'] and $criteria ['is_tot'] or $criteria ['is_tot'] === '0'));
// 	                            $criteria ['showRefresher'] = ($this->getSanParam ( 'showRefresher' ));
// 	                            $criteria ['showGotComment'] = ($this->getSanParam ( 'showGotComment' ));
// 	                            $criteria ['showPrimaryLanguage'] = ($this->getSanParam ( 'showPrimaryLanguage' ) or ($criteria ['doCount'] and $criteria ['training_primary_language_option_id'] or $criteria ['training_primary_language_option_id'] === '0'));
// 	                            $criteria ['showSecondaryLanguage'] = ($this->getSanParam ( 'showSecondaryLanguage' ) or ($criteria ['doCount'] and $criteria ['training_secondary_language_option_id'] or $criteria ['training_secondary_language_option_id'] === '0'));
// 	                            $criteria ['showFunding'] =   ($this->getSanParam ( 'showFunding' ) or ($criteria ['doCount'] and $criteria ['funding_id'] or $criteria ['funding_id'] === '0' or $criteria ['funding_min'] or $criteria ['funding_max']));
// 	                            $criteria ['showCategory'] =  ($this->getSanParam ( 'showCategory' ) or ($criteria ['doCount'] and $criteria ['training_category_id'] or $criteria ['training_category_id'] === '0'));
// 	                            $criteria ['showGotCurric'] = ($this->getSanParam ( 'showGotCurric' ) or ($criteria ['doCount'] and $criteria ['training_got_curric_id'] or $criteria ['training_got_curric_id'] === '0'));
// 	                            $criteria ['showCustom1'] =   ($this->getSanParam ( 'showCustom1' ));
// 	                            $criteria ['showCustom2'] =   ($this->getSanParam ( 'showCustom2' ));
// 	                            $criteria ['showCustom3'] =   ($this->getSanParam ( 'showCustom3' ));
// 	                            $criteria ['showCustom4'] =   ($this->getSanParam ( 'showCustom4' ));
// 	                            $criteria ['showCreatedBy'] = ($this->getSanParam ( 'showCreatedBy' ));
// 	                            $criteria['showCreationDate']=($this->getSanParam ( 'showCreationDate' ));
// 	                            $criteria ['showStartDate'] =   ($this->getSanParam ( 'showStartDate')); //TA:17: 9/3/2014
// 	                            $criteria ['showEndDate'] =   ($this->getSanParam ( 'showEndDate'));
// 	                            $criteria ['showRespPrim'] =  ($this->getSanParam ( 'showRespPrim' ));
// 	                            $criteria ['showRespSecond'] =($this->getSanParam ( 'showRespSecond' ));
// 	                            $criteria ['showHighestEd'] = ($this->getSanParam ( 'showHighestEd' ));
// 	                            //$criteria ['showReason'] =  ($this->getSanParam ( 'showReason' ));
// 	                            $criteria ['showAge'] =       ($this->getSanParam ( 'showAge' ) && $criteria ['doCount']) || ($this->getSanParam ( 'showAge' ) && $criteria ['doName']);
// 	                            $criteria ['showGender'] =    ($this->getSanParam ( 'showGender' ) && $criteria ['doCount']) || ($this->getSanParam ( 'showGender' ) && $criteria ['doName']);
// 	                            $criteria ['showViewingLoc'] = $this->getSanParam ( 'showViewingLoc');
// 	                            $criteria ['showQualPrim']   = 'on';
	                            
	                            
// 	                            $criteria ['training_participants_type'] = $this->getSanParam ( 'training_participants_type' );
	                            
// 	                            // row creation dates - explaination: server might be in NYC and client in Africa, server needs to check for trainings created at the day selected, minus the time difference (or plus it), accomplished by hidden input field storing clients javascript time. testing this (bugfix)
// 	                            $criteria['date_added'] = array();
// 	                            $userTime = $this->getSanParam('date_localtime') ? strtotime($this->getSanParam('date_localtime')) : time();
// 	                            if ( $criteria['creation_dates'][0] && !empty($criteria['creation_dates'][0]) ) {
// 	                                $difference = time() - $userTime;
// 	                                $date1 = strtotime( $criteria['creation_dates'][0]);
// 	                                $criteria['date_added'][0] = date( 'Y-m-d H:i:s', $date1 + $difference ); // keep the original date in same format for template
// 	                            }
// 	                            if ( $criteria['creation_dates'][1] && !empty($criteria['creation_dates'][1]) ) {
// 	                                $difference = time() - $userTime;
// 	                                $date2 = strtotime( $criteria['creation_dates'][1]);
// 	                                $date2 = strtotime("+1 day", $date2); // 11:59
// 	                                $criteria['date_added'][1] = date ('Y-m-d H:i:s', $date2 + $difference );
// 	                            }
	                            
// 	                            /////////////////////////////////
// 	                            // missing fields report
// 	                            //
// 	                            /////////////////////////////////
// 	                            if ($this->view->missing_info)
// 	                            {
// 	                                $flds = array(
// 	                                    'Training name'          =>			'training_title',
// 	                                    'Training end date'      =>			'training_end_date',
// 	                                    'Training organizer'     =>			'training_organizer_option_id',
// 	                                    'Training location'      =>			'training_location_id',
// 	                                    'Training level'         =>			'training_level_option_id',
// 	                                    'PEPFAR category'        =>			'tpep.training_pepfar_categories_option_id',
// 	                                    'Training Method'        =>			'training_method_option_id',
// 	                                    'Training topic'         =>			'ttopic.training_topic_option_id',
// 	                                    'Training of Trainers'   =>			'is_tot',
// 	                                    'Refresher course'       =>			'is_refresher',
// 	                                    'Funding'                =>			'tfund.training_funding_option_id',
// 	                                    'National curriculum'    =>			'training_got_curriculum_option_id',
// 	                                    'National curriculum comment' =>	'got_comments',
// 	                                    'Training Comments'      =>			'comments',
// 	                                    'Course Objectives'      =>			'course_id', //objectives
// 	                                    'Primary Language'       =>			'training_primary_language_option_id',
// 	                                    'Secondary Language'     =>			'training_secondary_language_option_id',
// 	                                    'No Trainers'            =>			'report_no_trainers',
// 	                                    'No Participant'         =>			'report_no_participants',
// 	                                    'No Scores for Participants' =>		'report_no_scores',
// 	                                    'Pre Test Average'       =>			'pre',
// 	                                    'Post Test Averages'     =>			'post',
// 	                                    'Custom 1'               =>			'training_custom_1_option_id',
// 	                                    'Custom 2'               =>			'training_custom_2_option_id',
// 	                                    'Custom 3'               =>			'custom_3',
// 	                                    'Custom 4'               =>			'custom_4',
// 	                                    'Approval Status'        =>			'is_approved',
// 	                                    'Approved Trainings'     =>			'report_is_approved1',
// 	                                    'Rejected Trainings'     =>			'report_is_approved0',
// 	                                    'With Attached Documents' =>		'report_with_attachments',
// 	                                    'WithOut Attached Documents' =>		'report_without_attachments'
// 	                                );
// 	                                $this->view->assign('flds', $flds); // we'll use these again in the view to print our options
	                                
// 	                                $criteria['searchflds'] = $this->getSanParam('searchflds'); // user selected these fields
// 	                                $w = array();	// temporary placeholder for our where clauses
// 	                                $normalFields = array(); // we can just use a 'where [normalField] is null' here
// 	                                // criteria and joins
// 	                                foreach ($criteria['searchflds'] as $i => $v) {
// 	                                    if ( $v == 'tpep.training_pepfar_categories_option_id' ) { $criteria ['showPepfar'] = 'on'; continue; }
// 	                                    if ( $v == 'ttopic.training_topic_option_id' ) { $criteria ['showTopic'] = 'on'; continue; }
// 	                                    if ( $v == 'tfund.training_funding_option_id' ) { $criteria ['showFunding'] = 'on'; continue; }
// 	                                    if ( $v == 'report_no_trainers' ) {         $w[] = 'pt.has_known_participants = 1 and pt.id not in (select distinct training_id from training_to_trainer)'; continue; }
// 	                                    if ( $v == 'report_no_participants' ) {     $w[] = 'pt.has_known_participants = 1 and pt.id not in (select distinct training_id from person_to_training )'; continue; }
// 	                                    if ( $v == 'report_no_scores' ) {           $w[] = 'pt.id not in (select distinct training_id from person_to_training inner join score on person_to_training_id = person_to_training.id)'; continue; }
// 	                                    if ( $v == 'report_is_approved1' ) {        $w[] = 'is_approved = 1'; continue; }
// 	                                    if ( $v == 'report_is_approved0' ) {        $w[] = 'is_approved = 0'; continue; }
// 	                                    if ( $v == 'report_with_attachments' ) {    $w[] = "pt.id   in   (select distinct parent_id from file where parent_table = 'training')"; continue; }
// 	                                    if ( $v == 'report_without_attachments' ) { $w[] = "pt.id not in (select distinct parent_id from file where parent_table = 'training')"; continue; }
// 	                                    $normalFields[] = $v;
// 	                                }
	                                
// 	                                // wheres
// 	                                foreach($normalFields as $row){
// 	                                    $w[] = "($row is null or $row = 0 or $row = '')";
// 	                                }
// 	                                if ( count($w) && $criteria['go'] )
// 	                                    $where[] = '(' . implode(' or ', $w) . ')';
// 	                            } // end missing fields report
	                            
// 	                            // defaults
// 	                            if (! $criteria ['go']) {
// 	                                $criteria ['showTrainingTitle'] = 1;
// 	                            }
	                            
// 	                            // run report
// 	                            if ($criteria ['go']) {
	                                
// 	                                $sql = 'SELECT ';
	                                
// 	                                if ($criteria ['doCount']) {
// 	                                    $sql .= ' COUNT(pt.person_id) as "cnt" ';
// 	                                } else {
// 	                                    //TA:110 show only those column in export Excel report
// 	                                    //		$sql .= ' DISTINCT pt.id as "id", ptc.pcnt, pt.training_start_date, pt.training_end_date, pt.has_known_participants  ';
// 	                                    $sql .= ' DISTINCT pt.id as "id", pt.training_start_date, pt.training_end_date ';
// 	                                }
	                                
// 	                                //TA:SA
// 	                                if ($criteria ['showTrainingStartDate']) {
// 	                                    $sql .= ', training_start_date ';
// 	                                }
	                                
// 	                                if ($criteria ['showTrainingTitle']) {
// 	                                    $sql .= ', training_title ';
// 	                                }
	                                
// 	                                if ($criteria ['showProvince']) {
// 	                                    $sql .= ', pt.province_name ';
// 	                                }
// 	                                if ($criteria ['showDistrict']) {
// 	                                    $sql .= ', pt.district_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showRegionC']) {
// 	                                    $sql .= ', pt.region_c_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showRegionD']) {
// 	                                    $sql .= ', pt.region_d_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showRegionE']) {
// 	                                    $sql .= ', pt.region_e_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showRegionG']) {
// 	                                    $sql .= ', pt.region_g_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showRegionH']) {
// 	                                    $sql .= ', pt.region_h_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showRegionI']) {
// 	                                    $sql .= ', pt.region_i_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showRegionF']) {
// 	                                    $sql .= ', pt.region_f_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showCategory']) {
// 	                                    $sql .= ', tcat.training_category_phrase ';
// 	                                }
	                                
	                                
// 	                                if ($criteria ['showLocation']) {
// 	                                    $sql .= ', pt.training_location_name ';
// 	                                }
	                                
// 	                                if ($criteria ['showOrganizer']) {
// 	                                    $sql .= ', torg.training_organizer_phrase ';
// 	                                }
	                                
// 	                                if ($criteria ['showMechanism'] && $display_training_partner) {
// 	                                    $sql .= ', organizer_partners.mechanism_id ';
// 	                                }
	                                
// 	                                if ($criteria ['showLevel']) {
// 	                                    $sql .= ', tlev.training_level_phrase ';
// 	                                }
	                                
// 	                                if ($criteria ['showMethod']) {
// 	                                    $sql .= ', tmeth.training_method_phrase ';
// 	                                }
	                                
// 	                                if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
// 	                                    if ($criteria ['doCount']) {
// 	                                        $sql .= ', tpep.pepfar_category_phrase ';
// 	                                    } else {
// 	                                        $sql .= ', GROUP_CONCAT(DISTINCT tpep.pepfar_category_phrase) as "pepfar_category_phrase" ';
// 	                                    }
// 	                                }
	                                
// 	                                if ($criteria ['showTopic']) {
// 	                                    if ($criteria ['doCount']) {
// 	                                        $sql .= ', ttopic.training_topic_phrase ';
// 	                                    } else {
// 	                                        $sql .= ', GROUP_CONCAT(DISTINCT ttopic.training_topic_phrase ORDER BY training_topic_phrase) AS "training_topic_phrase" ';
// 	                                    }
// 	                                }
	                                
// 	                                if ($criteria ['showTot']) {
// 	                                    $sql .= ", IF(is_tot,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_tot";
// 	                                }
	                                
// 	                                if ($criteria ['showRefresher']) {
// 	                                    $sql .= ", IF(is_refresher,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_refresher";
// 	                                }
	                                
// 	                                if ($criteria ['showSecondaryLanguage']) {
// 	                                    $sql .= ', tlos.language_phrase as "secondary_language_phrase" ';
// 	                                }
// 	                                if ($criteria ['showPrimaryLanguage']) {
// 	                                    $sql .= ', tlop.language_phrase as "primary_language_phrase" ';
// 	                                }
// 	                                if ($criteria ['showGotComment']) {
// 	                                    $sql .= ", pt.got_comments";
// 	                                }
// 	                                if ($criteria ['showGotCurric']) {
// 	                                    $sql .= ', tgotc.training_got_curriculum_phrase ';
// 	                                }
	                                
// 	                                if ($criteria ['showFunding']) {
// 	                                    if ($criteria ['doCount']) {
// 	                                        $sql .= ', tfund.funding_phrase ';
// 	                                    } else {
// 	                                        $sql .= ', GROUP_CONCAT(DISTINCT tfund.funding_phrase ORDER BY funding_phrase) as "funding_phrase" ';
// 	                                    }
// 	                                }
// 	                                if ( $criteria['showCustom1'] ) {
// 	                                    $sql .= ', tqc.custom1_phrase ';
// 	                                } // todo custom2-4
// 	                                if ( $criteria['showCreatedBy'] ) {
// 	                                    $sql .= ", CONCAT(user.first_name, CONCAT(' ', user.last_name)) as created_by_user ";
// 	                                }
// 	                                if ( $criteria['showCreationDate'] ) {
// 	                                    $sql .= ", DATE_FORMAT(pt.timestamp_created, '%Y-%m-%d') as created_date  ";
// 	                                }
// 	                                if ($criteria ['showGender']) {
// 	                                    $sql .= ', gender ';
// 	                                }
// 	                                if ($criteria ['showAge']) {
// 	                                    $sql .= ', age ';
// 	                                }
// 	                                if ($criteria ['showActive']) {
// 	                                    $sql .= ', pt.active ';
// 	                                }
// 	                                if ( $criteria['showViewingLoc'] ) {
// 	                                    $sql .= ', location_phrase, GROUP_CONCAT(DISTINCT location_phrase ORDER BY location_phrase) as "location_phrases" ';
// 	                                }
// 	                                if ( $criteria['showCustom1'] ) {
// 	                                    $sql .= ', tqc.custom1_phrase ';
// 	                                }
// 	                                if ( $criteria['showCustom2'] ) {
// 	                                    $sql .= ', tqc2.custom2_phrase';
// 	                                }
// 	                                if ( $criteria['showCustom3'] ) {
// 	                                    $sql .= ', pt.custom_3';
// 	                                }
// 	                                if ( $criteria['showCustom4'] ) {
// 	                                    $sql .= ', pt.custom_4';
// 	                                }
// 	                                if (($criteria['doCount'] && $criteria ['showQualPrim']) || ($criteria['doName'] && $criteria ['showQualPrim'])) {
// 	                                    $sql .= ', pq.qualification_phrase ';
// 	                                }
// 	                                if (($criteria['doCount'] && $criteria ['showQualSecond']) || ($criteria['doName'] && $criteria ['showQualSecond'])) {
// 	                                    $sql .= ', pqs.qualification_phrase AS qualification_secondary_phrase';
// 	                                }
	                                
// 	                                //TA:110 show participant column as a last
// 	                                if ($criteria ['doCount']) {
// 	                                } else {
// 	                                    $sql .= ', ptc.pcnt  ';
// 	                                }
	                                
	                                
// 	                                // prepare the location sub query
// 	                                $num_locs = $this->setting('num_location_tiers');
// 	                                list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);
	                                
// 	                                //if we're doing a participant count, then LEFT JOIN with the participants
// 	                                //otherwise just select the core training info
	                                
// 	                                if ($criteria ['doCount'] || $criteria ['doName']) {
// 	                                    $sql .= ' FROM (SELECT training.*, pers.person_id as "person_id", tto.training_title_phrase AS training_title, training_location.training_location_name, primary_qualification_option_id, pers.location_phrase as location_phrase,'.implode(',',$field_name).
// 	                                    '         FROM training ' .
// 	                                    '         LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id)' .
// 	                                    '         LEFT JOIN training_location ON training.training_location_id = training_location.id ' .
// 	                                    '         LEFT JOIN ('.$location_sub_query.') as l ON training_location.location_id = l.id ' .
// 	                                    '         LEFT JOIN (SELECT person_id,training_id, person_to_training_viewing_loc_option.location_phrase,primary_qualification_option_id,
// 											person.custom_3 as person_custom_3, person.custom_4 as person_custom_4, person.custom_5 as person_custom_5
// 										FROM person
// 										JOIN person_to_training ON person_to_training.person_id = person.id
// 										LEFT JOIN person_to_training_viewing_loc_option ON person_to_training.viewing_location_option_id = person_to_training_viewing_loc_option.id
// 									) as pers ON training.id = pers.training_id WHERE training.is_deleted=0) as pt ';
// 	                                } else {
// 	                                    $sql .= ' FROM (SELECT training.*, tto.training_title_phrase AS training_title,training_location.training_location_name, '.implode(',',$field_name).
// 	                                    '       FROM training  ' .
// 	                                    '         LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id) ' .
// 	                                    '         LEFT JOIN training_location ON training.training_location_id = training_location.id ' .
// 	                                    '         LEFT JOIN ('.$location_sub_query.') as l ON training_location.location_id = l.id ' .
// 	                                    '  WHERE training.is_deleted=0) as pt ';
// 	                                    //$sql .= " LEFT JOIN (SELECT COUNT(id) as `pcnt`,training_id FROM person_to_training GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
// 	                                    //TA:64 12/18/2015 take only persons which are not deleted
// 	                                    $sql .= " LEFT JOIN (SELECT COUNT(person_to_training.id) as `pcnt`,training_id FROM person_to_training left join person on person.id=person_to_training.person_id where person.is_deleted=0 GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
// 	                                }
// 	                                if ($criteria ['doName']) {
// 	                                    //$sql .= " LEFT JOIN (SELECT COUNT(id) as `pcnt`,training_id FROM person_to_training GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
// 	                                    //TA:64 12/18/2015 take only persons which are not deleted
// 	                                    $sql .= " LEFT JOIN (SELECT COUNT(person_to_training.id) as `pcnt`,training_id FROM person_to_training left join person on person.id=person_to_training.person_id where person.is_deleted=0 GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
// 	                                }
// 	                                if (!($criteria['doCount'] || $criteria['doName']) && ($criteria['showViewingLoc'] || $criteria['person_to_training_viewing_loc_option_id'])) {
// 	                                    $sql .= ' LEFT JOIN person_to_training ON person_id = person_to_training.person_id AND person_to_training.training_id = pt.id ';
// 	                                    $sql .= ' LEFT JOIN person_to_training_viewing_loc_option ON person_to_training.viewing_location_option_id = person_to_training_viewing_loc_option.id ';
// 	                                }
	                                
// 	                                if ($criteria ['showOrganizer'] or $criteria ['training_organizer_id'] || $criteria ['showMechanism']  || $criteria ['mechanism_id']) {
// 	                                    $sql .= '	JOIN training_organizer_option as torg ON torg.id = pt.training_organizer_option_id ';
// 	                                }
	                                
// 	                                if ($criteria ['showMechanism'] || $criteria ['mechanism_id'] && $display_training_partner) {
// 	                                    $sql .= ' LEFT JOIN organizer_partners ON organizer_partners.organizer_id = torg.id';
// 	                                }
	                                
// 	                                if ($criteria ['showLevel'] || $criteria ['training_level_id']) {
// 	                                    $sql .= '	JOIN training_level_option as tlev ON tlev.id = pt.training_level_option_id ';
// 	                                }
	                                
// 	                                if ($criteria ['showMethod'] || $criteria ['training_method_id']) {
// 	                                    $sql .= ' JOIN training_method_option as tmeth ON tmeth.id = pt.training_method_option_id ';
// 	                                }
	                                
// 	                                if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
// 	                                    $sql .= '	LEFT JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';
// 	                                }
	                                
// 	                                if ($criteria ['showTopic'] || $criteria ['training_topic_id']) {
// 	                                    $sql .= '	LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';
// 	                                }
	                                
// 	                                if ($criteria ['showPrimaryLanguage'] || $criteria ['training_primary_language_option_id']) {
// 	                                    $sql .= ' LEFT JOIN trainer_language_option as tlop ON tlop.id = pt.training_primary_language_option_id ';
// 	                                }
	                                
// 	                                if ($criteria ['showSecondaryLanguage'] || $criteria ['training_secondary_language_option_id']) {
// 	                                    $sql .= ' LEFT JOIN trainer_language_option as tlos ON tlos.id = pt.training_secondary_language_option_id ';
// 	                                }
	                                
// 	                                if ($criteria ['showFunding'] || (intval ( $criteria ['funding_min'] ) or intval ( $criteria ['funding_max'] ))) {
// 	                                    $sql .= '	LEFT JOIN (SELECT training_id, ttfo.training_funding_option_id, funding_phrase, ttfo.funding_amount FROM training_to_training_funding_option as ttfo JOIN training_funding_option as tfo ON ttfo.training_funding_option_id = tfo.id) as tfund ON tfund.training_id = pt.id ';
// 	                                }
	                                
// 	                                if ($criteria ['showGotCurric'] || $criteria ['training_got_curric_id']) {
// 	                                    $sql .= '	LEFT JOIN training_got_curriculum_option as tgotc ON tgotc.id = pt.training_got_curriculum_option_id';
// 	                                }
	                                
// 	                                if ($criteria ['showCategory'] or ! empty ( $criteria ['training_category_id'] )) {
// 	                                    $sql .= '
// 				LEFT JOIN training_category_option_to_training_title_option tcotto ON (tcotto.training_title_option_id = pt.training_title_option_id)
// 				LEFT JOIN training_category_option tcat ON (tcotto.training_category_option_id = tcat.id)
// 				';
// 	                                }
// 	                                if ( $criteria['showCustom1'] || $criteria ['custom_1_id'] ) {
// 	                                    $sql .= ' LEFT JOIN training_custom_1_option as tqc ON pt.training_custom_1_option_id = tqc.id  ';
// 	                                }
// 	                                if ( $criteria['showCustom2'] || $criteria ['custom_2_id'] ) {
// 	                                    $sql .= ' LEFT JOIN training_custom_2_option as tqc2 ON pt.training_custom_2_option_id = tqc2.id  ';
// 	                                }
	                                
// 	                                if ( $criteria['showCreatedBy'] || $criteria ['created_by'] ) {
// 	                                    $sql .= ' LEFT JOIN user ON user.id = pt.created_by  ';
// 	                                }
	                                
// 	                                if ($criteria['showGender'] || $criteria['showAge'] || $criteria['training_gender'] || $criteria['age_min'] || $criteria['age_max']) {
// 	                                    $personAlias  = ($criteria['doCount'] || $criteria['doName']) ? 'pt.person_id'  : 'person_id';
	                                    
// 	                                    $sql .= " LEFT JOIN person_to_training as ptt on ptt.training_id = pt.id AND $personAlias = ptt.person_id AND pt.is_deleted = 0 ";
// 	                                    $sql .= ' LEFT JOIN (SELECT id as pid, gender
// 								,CASE WHEN birthdate  IS NULL OR birthdate = \'0000-00-00\' THEN NULL ELSE ((date_format(now(),\'%Y\') - date_format(birthdate,\'%Y\')) - (date_format(now(),\'00-%m-%d\') < date_format(birthdate,\'00-%m-%d\')) ) END as "age"
// 								FROM person where is_deleted = 0) as perssexage ON perssexage.pid = ptt.person_id ';
// 	                                }
	                                
// 	                                if ( ($criteria['doCount'] || $criteria['doName']) && ($criteria ['showQualPrim'] || $criteria ['showQualSecond'] || $criteria ['qualification_id']  || $criteria ['qualification_secondary_id']) ) {
// 	                                    // primary qualifications
// 	                                    $sql .= 'LEFT JOIN person_qualification_option as pq ON (
// 							(pt.primary_qualification_option_id = pq.id AND pq.parent_id IS NULL)
// 							OR
// 							pq.id = (SELECT parent_id FROM person_qualification_option WHERE id = pt.primary_qualification_option_id LIMIT 1))';
	                                    
// 	                                    // secondary qualifications
// 	                                    $sql .= 'LEFT JOIN person_qualification_option as pqs ON (pt.primary_qualification_option_id = pqs.id AND pqs.parent_id IS NOT NULL)';
// 	                                }
	                                
// 	                                $where [] = ' pt.is_deleted=0 ';
	                                
// 	                                // restricted access?? only show trainings we have the ACL to view
// 	                                $org_allowed_ids = allowed_organizer_access($this);
// 	                                if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
// 	                                    $org_allowed_ids = implode(',', $org_allowed_ids);
// 	                                    $where [] = " pt.training_organizer_option_id in ($org_allowed_ids) ";
// 	                                }
// 	                                // restricted access?? only show organizers that belong to this site if its a multi org site
// 	                                $site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
// 	                                if ($site_orgs)
// 	                                    $where []= " pt.training_organizer_option_id in ($site_orgs) ";
	                                    
// 	                                    // criteria
// 	                                    if ($criteria ['training_participants_type']) {
// 	                                        if ($criteria ['training_participants_type'] == 'has_known_participants') {
// 	                                            $where [] = ' pt.has_known_participants = 1 ';
// 	                                        }
// 	                                        if ($criteria ['training_participants_type'] == 'has_unknown_participants') {
// 	                                            $where [] = ' pt.has_known_participants = 0 ';
	                                            
// 	                                        }
// 	                                    }
	                                    
	                                    
// 	                                    if ($this->_is_not_filter_all($criteria['training_title_option_id']) && ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0')) {
// 	                                        $where [] = 'pt.training_title_option_id in (' . $this->_sql_implode($criteria ['training_title_option_id']) . ')';
// 	                                    }
// 	                                    if ($criteria ['training_title']) {
// 	                                        $where [] = ' training_title = \'' . $criteria ['training_title'] . '\'';
// 	                                    }
// 	                                    if ($criteria ['training_location_id']) {
// 	                                        $where [] = ' pt.training_location_id = \'' . $criteria ['training_location_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($this->_is_not_filter_all($criteria['training_organizer_id']) && $criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0') {
// 	                                        $where [] = ' pt.training_organizer_option_id in (' . $this->_sql_implode($criteria ['training_organizer_id']) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['mechanism_id'] or $criteria ['mechanism_id'] === '0' && $display_training_partner) {
// 	                                        $where [] = ' organizer_partners.mechanism_id = \'' . $criteria ['mechanism_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($this->_is_not_filter_all($criteria['training_topic_id']) && $criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
// 	                                        $where [] = ' ttopic.training_topic_option_id in (' . $this->_sql_implode($criteria ['training_topic_id']) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['training_level_id']) {
// 	                                        $where [] = ' pt.training_level_option_id = \'' . $criteria ['training_level_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0') {
// 	                                        $where [] = ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['training_method_id'] or $criteria ['training_method_id'] === '0') {
// 	                                        $where [] = ' tmeth.id = \'' . $criteria ['training_method_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['training_primary_language_option_id'] or $criteria ['training_primary_language_option_id'] === '0') {
// 	                                        $where [] = ' pt.training_primary_language_option_id = \'' . $criteria ['training_primary_language_option_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['training_secondary_language_option_id'] or $criteria ['training_secondary_language_option_id'] === '0') {
// 	                                        $where [] = ' pt.training_secondary_language_option_id = \'' . $criteria ['training_secondary_language_option_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['province_id'] && ! empty ( $criteria ['province_id'] )) {
// 	                                        $where [] = ' pt.province_id IN (' . implode ( ',', $criteria ['province_id'] ) . ')';
// 	                                    }
	                                    
	                                    
// 	                                    if ($criteria ['district_id'] && ! empty ( $criteria ['district_id'] )) {
// 	                                        $where [] = ' pt.district_id IN (' . implode ( ',', $criteria ['district_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['region_c_id'] && ! empty ( $criteria ['region_c_id'] )) {
// 	                                        $where [] = ' pt.region_c_id IN (' . implode ( ',', $criteria ['region_c_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['region_d_id'] && ! empty ( $criteria ['region_d_id'] )) {
// 	                                        $where [] = ' pt.region_d_id IN (' . implode ( ',', $criteria ['region_d_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['region_e_id'] && ! empty ( $criteria ['region_e_id'] )) {
// 	                                        $where [] = ' pt.region_e_id IN (' . implode ( ',', $criteria ['region_e_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['region_f_id'] && ! empty ( $criteria ['region_f_id'] )) {
// 	                                        $where [] = ' pt.region_f_id IN (' . implode ( ',', $criteria ['region_f_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['region_g_id'] && ! empty ( $criteria ['region_g_id'] )) {
// 	                                        $where [] = ' pt.region_g_id IN (' . implode ( ',', $criteria ['region_g_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['region_h_id'] && ! empty ( $criteria ['region_h_id'] )) {
// 	                                        $where [] = ' pt.region_h_id IN (' . implode ( ',', $criteria ['region_h_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['region_i_id'] && ! empty ( $criteria ['region_i_id'] )) {
// 	                                        $where [] = ' pt.region_i_id IN (' . implode ( ',', $criteria ['region_i_id'] ) . ')';
// 	                                    }
	                                    
// 	                                    if (intval ( $criteria ['end-year'] ) and $criteria ['start-year']) {
// 	                                        $startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
// 	                                        $endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
// 	                                        $where [] = ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
// 	                                    }
	                                    
// 	                                    if (intval ( $criteria ['funding_min'] ) or intval ( $criteria ['funding_max'] )) {
// 	                                        if (intval ( $criteria ['funding_min'] ))
// 	                                            $where [] = 'tfund.funding_amount >= \'' . $criteria ['funding_min'] . '\' ';
// 	                                            if (intval ( $criteria ['funding_max'] ))
// 	                                                $where [] = 'tfund.funding_amount <= \'' . $criteria ['funding_max'] . '\' ';
// 	                                    }
	                                    
// 	                                    if (intval ( $criteria ['is_tot'] )) {
// 	                                        $where [] = ' is_tot = ' . $criteria ['is_tot'];
// 	                                    }
	                                    
// 	                                    if ($criteria ['funding_id'] or $criteria ['funding_id'] === '0') {
// 	                                        $where [] = ' tfund.training_funding_option_id = \'' . $criteria ['funding_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['training_category_id'] or $criteria ['training_category_id'] === '0') {
// 	                                        $where [] = ' tcat.id = \'' . $criteria ['training_category_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['training_got_curric_id'] or $criteria ['training_got_curric_id'] === '0') {
// 	                                        $where [] = ' tgotc.id = \'' . $criteria ['training_got_curric_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['custom_1_id'] or $criteria ['custom_1_id'] === '0') {
// 	                                        $where [] = ' pt.training_custom_1_option_id = \'' . $criteria ['custom_1_id'] . '\'';
// 	                                    }
// 	                                    if ($criteria ['custom_2_id'] or $criteria ['custom_2_id'] === '0') {
// 	                                        $where [] = ' pt.training_custom_2_option_id = \'' . $criteria ['custom_2_id'] . '\'';
// 	                                    }
// 	                                    if ($criteria ['custom_3_id'] or $criteria ['custom_3_id'] === '0') {
// 	                                        $where [] = ' pt.custom_3 = \'' . $criteria ['custom_3_id'] . '\'';
// 	                                    }
// 	                                    if ($criteria ['custom_4_id'] or $criteria ['custom_4_id'] === '0') {
// 	                                        $where [] = ' pt.custom_4 = \'' . $criteria ['custom_4_id'] . '\'';
// 	                                    }
	                                    
// 	                                    if ($criteria ['created_by'] or $criteria ['created_by'] === '0') {
// 	                                        $where [] = ' pt.created_by in (' . $this->_trainsmart_implode($criteria ['created_by']) . ')';
// 	                                    }
	                                    
// 	                                    if ($criteria ['date_added']) {
// 	                                        if ( isset( $criteria['date_added'][0] ) && !empty( $criteria['date_added'][0] ) ){
// 	                                            $where [] = " pt.timestamp_created >= '".$criteria['date_added'][0]."' ";
// 	                                        }
// 	                                        if ( isset( $criteria['date_added'][1] ) && !empty( $criteria['date_added'][1] ) ){
// 	                                            $where [] = " pt.timestamp_created <= '".$criteria['date_added'][1]."' ";
// 	                                        }
// 	                                    }
// 	                                    if ($criteria ['training_gender']) {
// 	                                        $where [] = " gender = '{$criteria['training_gender']}'";
// 	                                    }
	                                    
// 	                                    if ($criteria ['age_min']) {
// 	                                        $where [] = " age >= {$criteria['age_min']}";
// 	                                    }
	                                    
// 	                                    if ($criteria ['age_max']) {
// 	                                        $where [] = " age <= {$criteria['age_max']}";
// 	                                    }
	                                    
// 	                                    if ($criteria ['person_to_training_viewing_loc_option_id']) {
// 	                                        $where [] = 'person_to_training.viewing_location_option_id = ' . $criteria['person_to_training_viewing_loc_option_id'];
// 	                                    }
	                                    
// 	                                    if (($criteria['doCount'] && $criteria ['qualification_id']) || ($criteria['doName'] && $criteria ['qualification_id'])) {
// 	                                        $where [] = ' (pq.id = ' . $criteria ['qualification_id'] . ' OR pqs.parent_id = ' . $criteria ['qualification_id'] . ') ';
// 	                                    }
// 	                                    if (($criteria['doCount'] && $criteria ['qualification_secondary_id']) || ($criteria['doName'] && $criteria ['qualification_secondary_id'])) {
// 	                                        $where [] = ' pqs.id = ' . $criteria ['qualification_secondary_id'];
// 	                                    }
	                                    
// 	                                    if ($where)
// 	                                        $sql .= ' WHERE ' . implode ( ' AND ', $where );
	                                        
// 	                                        if ($criteria ['doCount']) {
	                                            
// 	                                            $groupBy = array();
	                                            
// 	                                            if ($criteria ['showTrainingTitle'])     $groupBy []=  '  pt.training_title_option_id';
// 	                                            if ($criteria ['showProvince'])          $groupBy []=  '  pt.province_id';
// 	                                            if ($criteria ['showDistrict'])          $groupBy []=  '  pt.district_id';
// 	                                            if ($criteria ['showRegionC'])           $groupBy []=  '  pt.region_c_id';
// 	                                            if ($criteria ['showRegionD'])           $groupBy []=  '  pt.region_d_id';
// 	                                            if ($criteria ['showRegionE'])           $groupBy []=  '  pt.region_e_id';
// 	                                            if ($criteria ['showRegionF'])           $groupBy []=  '  pt.region_f_id';
// 	                                            if ($criteria ['showRegionG'])           $groupBy []=  '  pt.region_g_id';
// 	                                            if ($criteria ['showRegionH'])           $groupBy []=  '  pt.region_h_id';
// 	                                            if ($criteria ['showRegionI'])           $groupBy []=  '  pt.region_i_id';
// 	                                            if ($criteria ['showLocation'])          $groupBy []=  '  pt.training_location_id';
// 	                                            if ($criteria ['showOrganizer'])         $groupBy []=  '  pt.training_organizer_option_id';
// 	                                            if ($criteria ['showMechanism'] && $display_training_partner) $groupBy []=  '  organizer_partners.mechanism_id';
// 	                                            if ($criteria ['showCustom1'])           $groupBy []=  '  pt.training_custom_1_option_id';
// 	                                            if ($criteria ['showCustom2'])           $groupBy []=  '  pt.training_custom_2_option_id';
// 	                                            if ($criteria ['showCustom3'])           $groupBy []=  '  pt.custom_3';
// 	                                            if ($criteria ['showCustom4'])           $groupBy []=  '  pt.custom_4';
// 	                                            if ($criteria ['showTopic'])             $groupBy []=  '  ttopic.training_topic_option_id';
// 	                                            if ($criteria ['showLevel'])             $groupBy []=  '  pt.training_level_option_id';
// 	                                            if ($criteria ['showPepfar'])            $groupBy []=  '  tpep.training_pepfar_categories_option_id';
// 	                                            if ($criteria ['showMethod'])            $groupBy []=  '  tmeth.id';
// 	                                            if ($criteria ['showTot'])               $groupBy []=  '  pt.is_tot';
// 	                                            if ($criteria ['showRefresher'])         $groupBy []=  '  pt.is_refresher';
// 	                                            if ($criteria ['showGotCurric'])         $groupBy []=  '  pt.training_got_curriculum_option_id';
// 	                                            if ($criteria ['showPrimaryLanguage'])   $groupBy []=  '  pt.training_primary_language_option_id';
// 	                                            if ($criteria ['showSecondaryLanguage']) $groupBy []=  '  pt.training_secondary_language_option_id';
// 	                                            if ($criteria ['showFunding'])           $groupBy []=  '  tfund.training_funding_option_id';
// 	                                            if ($criteria ['showCreatedBy'])         $groupBy []=  '  pt.created_by';
// 	                                            if ($criteria ['showCreationDate'])      $groupBy []=  '  pt.timestamp_created';
// 	                                            if ($criteria ['showGender'])            $groupBy []=  '  gender';
// 	                                            if ($criteria ['showAge'])               $groupBy []=  '  age';
// 	                                            if ($criteria ['showViewingLoc'])        $groupBy []=  '  location_phrase';
// 	                                            if ($criteria ['showQualPrim'])          $groupBy []=  '  pq.qualification_phrase';
// 	                                            if ($criteria ['showQualSecond'])        $groupBy []=  '  pqs.qualification_phrase';
	                                            
// 	                                            if ($groupBy) {
// 	                                                $sql .= ' GROUP BY ' . implode(',',$groupBy);
// 	                                            }
	                                            
// 	                                            if ($criteria['showAge'] || $criteria['showGender']) {
// 	                                                $sql .= ' HAVING count(pt.person_id) > 0 ';
// 	                                            }
// 	                                        } else {    
// 	                                            $sql .= ' GROUP BY pt.id';                                  
// 	                                        }
// 	                                        if ($this->view->mode == 'search') {
// 	                                            $sql .= ' ORDER BY training_start_date DESC';
// 	                                        }
	                                        
// 	                                        //TA:UK 
// 	                                        print $sql;
// 	                                        $rowArray = $db->fetchAll ( $sql );
	                                        
// 	                                        if ($criteria ['doCount']) {
// 	                                            $count = 0;
// 	                                            foreach ( $rowArray as $row ) {
// 	                                                $count += $row ['cnt'];
// 	                                            }
// 	                                        } else {
// 	                                            $count = count ( $rowArray );
// 	                                        }
	                                        
// 	                                        if ($this->getParam ( 'outputType' )){
// 	                                            $this->sendData ( $this->reportHeaders ( false, $rowArray ) ); //TA:110 export Excel/csv report - array inside has correct encoded cyrillic
// 	                                        }                                        
// 	                            } else {
// 	                                $count = 0;
// 	                                $rowArray = array ();
// 	                            }                            
// 	}

public function trainingByParticipantcountAction(){
    
    $this->view->assign ( 'mode', 'count' );

//by person facilities
$sql = "SELECT 
    COUNT(pt.person_id) AS 'cnt',
    training_start_date,
    training_title,
    /*pt.province_name,
    pt.district_name,
    pt.region_c_name,*/
    pt.training_location_name as 'Training location',
    province_name_f as 'Province',
    district_name_f as 'District',
    region_c_name_f as 'Sub-District',
    facility.facility_name,
    pq.qualification_phrase
FROM
    (SELECT 
        training.*,
            pers.person_id AS 'person_id',
            tto.training_title_phrase AS training_title,
            training_location.training_location_name,
            primary_qualification_option_id,
            pers.location_phrase AS location_phrase,
            province_name,
            province_id,
            district_name,
            district_id,
            region_c_name,
            region_c_id,
            city_name,
            city_id,
            facility_id
    FROM
        training
    LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id)
    LEFT JOIN training_location ON training.training_location_id = training_location.id
    LEFT JOIN (SELECT DISTINCT
        l4.id AS id,
            l4.location_name AS city_name,
            l4.id AS city_id,
            l3.location_name AS region_c_name,
            l3.id AS region_c_id,
            l2.location_name AS district_name,
            l2.id AS district_id,
            l1.location_name AS province_name,
            l1.id AS province_id
    FROM
        location l4
    LEFT JOIN location l3 ON l4.parent_id = l3.id AND l3.tier = 3
    LEFT JOIN location l2 ON l3.parent_id = l2.id AND l2.tier = 2
    LEFT JOIN location l1 ON l2.parent_id = l1.id AND l1.tier = 1
    WHERE
        l4.tier = 4 UNION SELECT DISTINCT
        l3.id AS id,
            'unknown' AS city_name,
            'unknown' AS city_id,
            l3.location_name AS region_c_name,
            l3.id AS region_c_id,
            l2.location_name AS district_name,
            l2.id AS district_id,
            l1.location_name AS province_name,
            l1.id AS province_id
    FROM
        location l3
    LEFT JOIN location l2 ON l3.parent_id = l2.id AND l2.tier = 2
    LEFT JOIN location l1 ON l2.parent_id = l1.id AND l1.tier = 1
    WHERE
        l3.tier = 3) AS l ON training_location.location_id = l.id
    LEFT JOIN (SELECT 
        person_id,
            training_id,
            person_to_training_viewing_loc_option.location_phrase,
            primary_qualification_option_id,
            person.custom_3 AS person_custom_3,
            person.custom_4 AS person_custom_4,
            person.custom_5 AS person_custom_5,
            facility_id
    FROM
        person
        
    JOIN person_to_training ON person_to_training.person_id = person.id
    LEFT JOIN person_to_training_viewing_loc_option ON person_to_training.viewing_location_option_id = person_to_training_viewing_loc_option.id) AS pers ON training.id = pers.training_id
    WHERE
        training.is_deleted = 0) AS pt
        LEFT JOIN
    person_qualification_option AS pq ON ((pt.primary_qualification_option_id = pq.id
        AND pq.parent_id IS NULL)
        OR pq.id = (SELECT 
            parent_id
        FROM
            person_qualification_option
        WHERE
            id = pt.primary_qualification_option_id
        LIMIT 1))
        LEFT JOIN
    person_qualification_option AS pqs ON (pt.primary_qualification_option_id = pqs.id
        AND pqs.parent_id IS NOT NULL)
	JOIN facility on facility.id=pt.facility_id
    LEFT JOIN (SELECT DISTINCT
        l4_f.id AS id_f,
            l4_f.location_name AS city_name_f,
            l4_f.id AS city_id_f,
            l3_f.location_name AS region_c_name_f,
            l3_f.id AS region_c_id_f,
            l2_f.location_name AS district_name_f,
            l2_f.id AS district_id_f,
            l1_f.location_name AS province_name_f,
            l1_f.id AS province_id_f
    FROM
        location l4_f
    LEFT JOIN location l3_f ON l4_f.parent_id = l3_f.id AND l3_f.tier = 3
    LEFT JOIN location l2_f ON l3_f.parent_id = l2_f.id AND l2_f.tier = 2
    LEFT JOIN location l1_f ON l2_f.parent_id = l1_f.id AND l1_f.tier = 1
    WHERE
        l4_f.tier = 4 UNION SELECT DISTINCT
        l3_f.id AS id_f,
            'unknown' AS city_name_f,
            'unknown' AS city_id_f,
            l3_f.location_name AS region_c_name_f,
            l3_f.id AS region_c_id_f,
            l2_f.location_name AS district_name_f,
            l2_f.id AS district_id_f,
            l1_f.location_name AS province_name_f,
            l1_f.id AS province_id_f
    FROM
        location l3_f
    LEFT JOIN location l2_f ON l3_f.parent_id = l2_f.id AND l2_f.tier = 2
    LEFT JOIN location l1_f ON l2_f.parent_id = l1_f.id AND l1_f.tier = 1
    WHERE
        l3_f.tier = 3) AS l_f ON facility.location_id = l_f.id_f
WHERE
    pt.is_deleted = 0
       AND training_title = '" . $this->getSanParam ( 'training_title' ) . "'
GROUP BY training_start_date, pt.training_location_id, facility.id, pq.qualification_phrase
order by pt.training_location_id, training_start_date, facility.facility_name, pq.qualification_phrase;";

/////////// consolidate data
require_once 'Zend/Loader.php';
require_once 'Zend/Db.php';
require_once 'Zend/Db/Table/Abstract.php';

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_gauteng'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_gauteng = $db->fetchAll ( $sql );

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_limpopo'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_limpopo = $db->fetchAll ( $sql );

$rowArray = array_merge($rowArray_gauteng, $rowArray_limpopo);

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_mpumalanga'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_mpumalanga = $db->fetchAll ( $sql );

$rowArray = array_merge($rowArray, $rowArray_mpumalanga);

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_easterncape'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_easterncape = $db->fetchAll ( $sql );

$rowArray = array_merge($rowArray, $rowArray_easterncape);

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_freestate'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_freestate = $db->fetchAll ( $sql );

$rowArray = array_merge($rowArray, $rowArray_freestate);

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_northerncape'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_northerncape = $db->fetchAll ( $sql );

$rowArray = array_merge($rowArray, $rowArray_northerncape);

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_northwest'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_northwest = $db->fetchAll ( $sql );

$rowArray = array_merge($rowArray, $rowArray_northwest);

$db = Zend_Db::factory('PDO_MYSQL', array(
    'host' => Settings::$DB_SERVER,
    'username' => Settings::$DB_USERNAME,
    'password' => Settings::$DB_PWD,
    'dbname' => 'itechweb_westerncape'
));
Zend_Db_Table_Abstract::setDefaultAdapter($db);
$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$rowArray_westerncape = $db->fetchAll ( $sql );

$rowArray = array_merge($rowArray, $rowArray_westerncape);

////////////////////////////////

//$rowArray = $db->fetchAll ( $sql ); // if not consolidation required then uncomment
                                        
                                        if ($this->getParam ( 'outputType' )){
                                            $this->sendData ( $this->reportHeaders ( false, $rowArray ) ); 
                                        }
                       
}

	public function trainingByTitleAction() {
		$this->view->assign ( 'mode', 'name' );
		$this->view->assign ( 'expand_lists', 1 );

		return $this->trainingReport ();
	}

	public function trainingsMissingInformationAction(){
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'missing_info', 1 );

		return $this->trainingReport ();
	}

	public function trainingReport() {
		$this->_countrySettings = array();
		$this->_countrySettings = System::getAll();

		require_once ('models/table/TrainingLocation.php');
		require_once('views/helpers/TrainingViewHelper.php');

		$criteria = array ();
		$where = array ();
		$display_training_partner = ( isset($this->_countrySettings['display_training_partner']) && $this->_countrySettings['display_training_partner'] == 1 ) ? true : false;

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = @$parts [0];
		$criteria ['start-month'] = @$parts [1];
		$criteria ['start-day'] = @$parts [2];

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		if ($this->view->mode == 'search') {
			$sql = "SELECT MAX(training_start_date) as \"start\" FROM training ";
			$rowArray = $db->fetchAll ( $sql );
			$end_default = $rowArray [0] ['start'];
			$parts = explode('-', $end_default );
			$criteria ['end-year'] = @$parts [0];
			$criteria ['end-month'] = @$parts [1];
			$criteria ['end-day'] = @$parts [2];
		} else {
			$criteria ['end-year'] = date ( 'Y' );
			$criteria ['end-month'] = date ( 'm' );
			$criteria ['end-day'] = date ( 'd' );
		}

		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );

		// find training name from new category/title format: categoryid_titleid
		$ct_ids = $criteria ['training_category_and_title_id'] = $this->getSanParam ( 'training_category_and_title_id' );
		$criteria ['training_title_option_id'] = $this->_pop_all($ct_ids);

		$criteria ['training_location_id'] =                     $this->getSanParam ( 'training_location_id' );
		$criteria ['training_organizer_id'] =                    $this->getSanParam ( 'training_organizer_id' );
		$criteria ['training_pepfar_id'] =                       $this->getSanParam ( 'training_pepfar_id' );
		$criteria ['training_method_id'] =                       $this->getSanParam ( 'training_method_id' );
		$criteria ['mechanism_id'] =                             $this->getSanParam ( 'mechanism_id' );
		$criteria ['training_topic_id'] =                        $this->getSanParam ( 'training_topic_id' );
		$criteria ['training_level_id'] =                        $this->getSanParam ( 'training_level_id' );
		$criteria ['training_primary_language_option_id'] =      $this->getSanParam ( 'training_primary_language_option_id' );
		$criteria ['training_secondary_language_option_id'] =    $this->getSanParam ( 'training_secondary_language_option_id' );
		$criteria ['training_category_id'] =                     $this->getSanParam ( 'training_category_id' ); //reset(explode('_',$ct_ids));//
		$criteria ['training_got_curric_id'] =                   $this->getSanParam ( 'training_got_curric_id' );
		$criteria ['is_tot'] =                                   $this->getSanParam ( 'is_tot' );
		$criteria ['funding_id'] =                               $this->getSanParam ( 'funding_id' );
		$criteria ['custom_1_id'] =                              $this->getSanParam ( 'custom_1_id' );
		$criteria ['custom_2_id'] =                              $this->getSanParam ( 'custom_2_id' );
		$criteria ['custom_3_id'] =                              $this->getSanParam ( 'custom_3_id' );
		$criteria ['custom_4_id'] =                              $this->getSanParam ( 'custom_4_id' );
		$criteria ['created_by'] =                               $this->getSanParam ( 'created_by' );
		$criteria ['creation_dates'] =                           $this->getSanParam ( 'creation_dates' );
		$criteria ['funding_min'] =                              $this->getSanParam ( 'funding_min' );
		$criteria ['funding_max'] =                              $this->getSanParam ( 'funding_max' );
		$criteria ['refresher_id'] =                             $this->getSanParam ( 'refresher_id' );
		$criteria ['person_to_training_viewing_loc_option_id'] = $this->getSanParam('person_to_training_viewing_loc_option_id');
		$criteria ['primary_responsibility_option_id'] =         $this->getSanParam ( 'primary_responsibility_option_id' );
		$criteria ['secondary_responsibility_option_id'] =       $this->getSanParam ( 'secondary_responsibility_option_id' );
		$criteria ['highest_edu_level_option_id'] =              $this->getSanParam ( 'highest_edu_level_option_id' );
		//$criteria ['attend_reason_option_id'] = $this->getSanParam ( 'attend_reason_option_id' );
		$criteria ['qualification_id'] =                         $this->getSanParam ( 'qualification_id' );
		$criteria ['qualification_secondary_id'] =               $this->getSanParam ( 'qualification_secondary_id' );
		$criteria ['doCount'] =       ($this->view->mode == 'count');
		$criteria ['doName'] =       ($this->view->mode == 'name');
		
		if($criteria['doCount'] || $criteria ['doName']) {
			$criteria ['age_max'] =                                $this->getSanParam ( 'age_max' );
			$criteria ['age_min'] =                                $this->getSanParam ( 'age_min' );
			$criteria ['training_gender'] =                       $this->getSanParam ( 'training_gender' );
		}
		
		//TA:26 fix bug, get http parameter
		$criteria ['province_id'] = $this->getSanParam ( 'province_id' );
		$arr_dist = $this->getSanParam ( 'district_id' );
		// level 2 location has parameter as [parent_location_id]_[location_id], we need to take only location_ids
		for($i=0;$i<sizeof($arr_dist); $i++){
			if ( strstr($arr_dist[$i], '_') !== false ) {
				$parts = explode('_',$arr_dist[$i]);
				$arr_dist[$i] = $parts[1];
			}
		}
		$criteria ['district_id'] = $arr_dist;

		$criteria ['go'] = $this->getSanParam ( 'go' );
		$criteria ['showProvince'] =  ($this->getSanParam ( 'showProvince' ) or ($criteria ['doCount'] and ($criteria ['province_id'] or ! empty ( $criteria ['province_id'] ))));
		$criteria ['showDistrict'] =  ($this->getSanParam ( 'showDistrict' ) or ($criteria ['doCount'] and ($criteria ['district_id'] or ! empty ( $criteria ['district_id'] ))));
		$criteria ['showRegionC'] =   ($this->getSanParam ( 'showRegionC' ) or ($criteria ['doCount'] and ($criteria ['region_c_id'] or ! empty ( $criteria ['region_c_id'] ))));
		$criteria ['showRegionD'] =   ($this->getSanParam ( 'showRegionD' ) or ($criteria ['doCount'] and ($criteria ['region_d_id'] or ! empty ( $criteria ['region_d_id'] ))));
		$criteria ['showRegionE'] =   ($this->getSanParam ( 'showRegionE' ) or ($criteria ['doCount'] and ($criteria ['region_e_id'] or ! empty ( $criteria ['region_e_id'] ))));
		$criteria ['showRegionF'] =   ($this->getSanParam ( 'showRegionF' ) or ($criteria ['doCount'] and ($criteria ['region_f_id'] or ! empty ( $criteria ['region_f_id'] ))));
		$criteria ['showRegionG'] =   ($this->getSanParam ( 'showRegionG' ) or ($criteria ['doCount'] and ($criteria ['region_g_id'] or ! empty ( $criteria ['region_g_id'] ))));
		$criteria ['showRegionH'] =   ($this->getSanParam ( 'showRegionH' ) or ($criteria ['doCount'] and ($criteria ['region_h_id'] or ! empty ( $criteria ['region_h_id'] ))));
		$criteria ['showRegionI'] =   ($this->getSanParam ( 'showRegionI' ) or ($criteria ['doCount'] and ($criteria ['region_i_id'] or ! empty ( $criteria ['region_i_id'] ))));
		$criteria ['showTrainingTitle'] = ($this->getSanParam ( 'showTrainingTitle' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0')));
		$criteria ['showLocation'] =  ($this->getSanParam ( 'showLocation' ) or ($criteria ['doCount'] and $criteria ['training_location_id']));
		$criteria ['showOrganizer'] = ($this->getSanParam ( 'showOrganizer' ) or ($criteria ['doCount'] and ($criteria ['training_organizer_id'])));
		$criteria ['showMechanism'] = ($this->getSanParam ( 'showMechanism' ) or ($criteria ['doCount'] and $criteria ['mechanism_id']));
		$criteria ['showPepfar'] =    ($this->getSanParam ( 'showPepfar' ) or ($criteria ['doCount'] and ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0')));
		$criteria ['showMethod'] =    ($this->getSanParam ( 'showMethod' ) or ($criteria ['doCount'] and ($criteria ['training_method_id'] or $criteria ['training_method_id'] === '0')));
		$criteria ['showTopic'] =     ($this->getSanParam ( 'showTopic' ) or ($criteria ['doCount'] and ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')));
		$criteria ['showLevel'] =     ($this->getSanParam ( 'showLevel' ) or ($criteria ['doCount'] and $criteria ['training_level_id']));
		$criteria ['showTot'] =       ($this->getSanParam ( 'showTot' ) or ($criteria ['doCount'] and $criteria ['is_tot'] or $criteria ['is_tot'] === '0'));
		$criteria ['showRefresher'] = ($this->getSanParam ( 'showRefresher' ));
		$criteria ['showGotComment'] = ($this->getSanParam ( 'showGotComment' ));
		$criteria ['showPrimaryLanguage'] = ($this->getSanParam ( 'showPrimaryLanguage' ) or ($criteria ['doCount'] and $criteria ['training_primary_language_option_id'] or $criteria ['training_primary_language_option_id'] === '0'));
		$criteria ['showSecondaryLanguage'] = ($this->getSanParam ( 'showSecondaryLanguage' ) or ($criteria ['doCount'] and $criteria ['training_secondary_language_option_id'] or $criteria ['training_secondary_language_option_id'] === '0'));
		$criteria ['showFunding'] =   ($this->getSanParam ( 'showFunding' ) or ($criteria ['doCount'] and $criteria ['funding_id'] or $criteria ['funding_id'] === '0' or $criteria ['funding_min'] or $criteria ['funding_max']));
		$criteria ['showCategory'] =  ($this->getSanParam ( 'showCategory' ) or ($criteria ['doCount'] and $criteria ['training_category_id'] or $criteria ['training_category_id'] === '0'));
		$criteria ['showGotCurric'] = ($this->getSanParam ( 'showGotCurric' ) or ($criteria ['doCount'] and $criteria ['training_got_curric_id'] or $criteria ['training_got_curric_id'] === '0'));
		$criteria ['showCustom1'] =   ($this->getSanParam ( 'showCustom1' ));
		$criteria ['showCustom2'] =   ($this->getSanParam ( 'showCustom2' ));
		$criteria ['showCustom3'] =   ($this->getSanParam ( 'showCustom3' ));
		$criteria ['showCustom4'] =   ($this->getSanParam ( 'showCustom4' ));
		$criteria ['showCreatedBy'] = ($this->getSanParam ( 'showCreatedBy' ));
		$criteria['showCreationDate']=($this->getSanParam ( 'showCreationDate' ));
		$criteria ['showStartDate'] =   ($this->getSanParam ( 'showStartDate')); //TA:17: 9/3/2014
		$criteria ['showEndDate'] =   ($this->getSanParam ( 'showEndDate'));
		$criteria ['showRespPrim'] =  ($this->getSanParam ( 'showRespPrim' ));
		$criteria ['showRespSecond'] =($this->getSanParam ( 'showRespSecond' ));
		$criteria ['showHighestEd'] = ($this->getSanParam ( 'showHighestEd' ));
		//$criteria ['showReason'] =  ($this->getSanParam ( 'showReason' ));
		$criteria ['showAge'] =       ($this->getSanParam ( 'showAge' ) && $criteria ['doCount']) || ($this->getSanParam ( 'showAge' ) && $criteria ['doName']);
		$criteria ['showGender'] =    ($this->getSanParam ( 'showGender' ) && $criteria ['doCount']) || ($this->getSanParam ( 'showGender' ) && $criteria ['doName']);
		$criteria ['showViewingLoc'] = $this->getSanParam ( 'showViewingLoc');
		$criteria ['showQualPrim']   = $this->getSanParam ( 'showQualPrim');
		$criteria ['showQualSecond'] = $this->getSanParam ( 'showQualSecond');

		$criteria ['training_participants_type'] = $this->getSanParam ( 'training_participants_type' );

		// row creation dates - explaination: server might be in NYC and client in Africa, server needs to check for trainings created at the day selected, minus the time difference (or plus it), accomplished by hidden input field storing clients javascript time. testing this (bugfix)
		$criteria['date_added'] = array();
		$userTime = $this->getSanParam('date_localtime') ? strtotime($this->getSanParam('date_localtime')) : time();
		if ( $criteria['creation_dates'][0] && !empty($criteria['creation_dates'][0]) ) {
			$difference = time() - $userTime;
			$date1 = strtotime( $criteria['creation_dates'][0]);
			$criteria['date_added'][0] = date( 'Y-m-d H:i:s', $date1 + $difference ); // keep the original date in same format for template
		}
		if ( $criteria['creation_dates'][1] && !empty($criteria['creation_dates'][1]) ) {
			$difference = time() - $userTime;
			$date2 = strtotime( $criteria['creation_dates'][1]);
			$date2 = strtotime("+1 day", $date2); // 11:59
			$criteria['date_added'][1] = date ('Y-m-d H:i:s', $date2 + $difference );
		}

		/////////////////////////////////
		// missing fields report
		//
		/////////////////////////////////
		if ($this->view->missing_info)
		{
			$flds = array(
				'Training name'          =>			'training_title_option_id',
				'Training end date'      =>			'training_end_date',
				'Training organizer'     =>			'training_organizer_option_id',
				'Training location'      =>			'training_location_id',
				'Training level'         =>			'training_level_option_id',
				'PEPFAR category'        =>			'tpep.training_pepfar_categories_option_id',
				'Training Method'        =>			'training_method_option_id',
				'Training topic'         =>			'ttopic.training_topic_option_id',
				'Training of Trainers'   =>			'is_tot',
				'Refresher course'       =>			'is_refresher',
				'Funding'                =>			'tfund.training_funding_option_id',
				'National curriculum'    =>			'training_got_curriculum_option_id',
				'National curriculum comment' =>	'got_comments',
				'Training Comments'      =>			'comments',
				'Course Objectives'      =>			'course_id', //objectives
				'Primary Language'       =>			'training_primary_language_option_id',
				'Secondary Language'     =>			'training_secondary_language_option_id',
				'No Trainers'            =>			'report_no_trainers',
				'No Participant'         =>			'report_no_participants',
				'No Scores for Participants' =>		'report_no_scores',
				'Pre Test Average'       =>			'pre',
				'Post Test Averages'     =>			'post',
				'Custom 1'               =>			'training_custom_1_option_id',
				'Custom 2'               =>			'training_custom_2_option_id',
				'Custom 3'               =>			'custom_3',
				'Custom 4'               =>			'custom_4',
				'Approval Status'        =>			'is_approved',
				'Approved Trainings'     =>			'report_is_approved1',
				'Rejected Trainings'     =>			'report_is_approved0',
				'With Attached Documents' =>		'report_with_attachments',
				'WithOut Attached Documents' =>		'report_without_attachments'
				);
			$this->view->assign('flds', $flds); // we'll use these again in the view to print our options

			$criteria['searchflds'] = $this->getSanParam('searchflds'); // user selected these fields
			$w = array();	// temporary placeholder for our where clauses
			$normalFields = array(); // we can just use a 'where [normalField] is null' here
			// criteria and joins
			foreach ($criteria['searchflds'] as $i => $v) {
				if ( $v == 'tpep.training_pepfar_categories_option_id' ) { $criteria ['showPepfar'] = 'on'; continue; }
				if ( $v == 'ttopic.training_topic_option_id' ) { $criteria ['showTopic'] = 'on'; continue; }
				if ( $v == 'tfund.training_funding_option_id' ) { $criteria ['showFunding'] = 'on'; continue; }
				if ( $v == 'report_no_trainers' ) {         $w[] = 'pt.has_known_participants = 1 and pt.id not in (select distinct training_id from training_to_trainer)'; continue; }
				if ( $v == 'report_no_participants' ) {     $w[] = 'pt.has_known_participants = 1 and pt.id not in (select distinct training_id from person_to_training )'; continue; }
				if ( $v == 'report_no_scores' ) {           $w[] = 'pt.id not in (select distinct training_id from person_to_training inner join score on person_to_training_id = person_to_training.id)'; continue; }
				if ( $v == 'report_is_approved1' ) {        $w[] = 'is_approved = 1'; continue; }
				if ( $v == 'report_is_approved0' ) {        $w[] = 'is_approved = 0'; continue; }
				if ( $v == 'report_with_attachments' ) {    $w[] = "pt.id   in   (select distinct parent_id from file where parent_table = 'training')"; continue; }
				if ( $v == 'report_without_attachments' ) { $w[] = "pt.id not in (select distinct parent_id from file where parent_table = 'training')"; continue; }
				$normalFields[] = $v;
			}

			// wheres
			foreach($normalFields as $row){
				$w[] = "($row is null or $row = 0 or $row = '')";
			}
			if ( count($w) && $criteria['go'] )
				$where[] = '(' . implode(' or ', $w) . ')';
		} // end missing fields report

		// defaults
		if (! $criteria ['go']) {
			$criteria ['showTrainingTitle'] = 1;
		}

		// run report
		if ($criteria ['go']) {

			$sql = 'SELECT ';

			if ($criteria ['doCount']) {
				$sql .= ' COUNT(pt.person_id) as "cnt" ';
			} else {
				//TA:110 show only those column in export Excel report
 		//		$sql .= ' DISTINCT pt.id as "id", ptc.pcnt, pt.training_start_date, pt.training_end_date, pt.has_known_participants  ';
			    $sql .= ' DISTINCT pt.id as "id", pt.training_start_date, pt.training_end_date ';
			}

			if ($criteria ['showRegionI']) {
				$sql .= ', pt.region_i_name ';
			}
			if ($criteria ['showRegionH']) {
				$sql .= ', pt.region_h_name ';
			}
			if ($criteria ['showRegionG']) {
				$sql .= ', pt.region_g_name ';
			}
			if ($criteria ['showRegionF']) {
				$sql .= ', pt.region_f_name ';
			}
			if ($criteria ['showRegionE']) {
				$sql .= ', pt.region_e_name ';
			}
			if ($criteria ['showRegionD']) {
				$sql .= ', pt.region_d_name ';
			}
			if ($criteria ['showRegionC']) {
				$sql .= ', pt.region_c_name ';
			}
			if ($criteria ['showProvince']) {
			    $sql .= ', pt.province_name ';
			}
			if ($criteria ['showDistrict']) {
				$sql .= ', pt.district_name ';
			}
			
		    if ($criteria ['showCategory']) {
				$sql .= ', tcat.training_category_phrase ';
			}
			
			if ($criteria ['showTrainingTitle']) {
			    $sql .= ', training_title ';
			}
			

			if ($criteria ['showLocation']) {
				$sql .= ', pt.training_location_name ';
			}

			if ($criteria ['showOrganizer']) {
				$sql .= ', torg.training_organizer_phrase ';
			}

			if ($criteria ['showMechanism'] && $display_training_partner) {
				$sql .= ', organizer_partners.mechanism_id ';
			}

			if ($criteria ['showLevel']) {
				$sql .= ', tlev.training_level_phrase ';
			}
			
			if ($criteria ['showMethod']) {
			    $sql .= ', tmeth.training_method_phrase ';
			}

			if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
				if ($criteria ['doCount']) {
					$sql .= ', tpep.pepfar_category_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT tpep.pepfar_category_phrase) as "pepfar_category_phrase" ';
				}
			}

			if ($criteria ['showTopic']) {
				if ($criteria ['doCount']) {
					$sql .= ', ttopic.training_topic_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT ttopic.training_topic_phrase ORDER BY training_topic_phrase) AS "training_topic_phrase" ';
				}
			}

			if ($criteria ['showTot']) {
				$sql .= ", IF(is_tot,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_tot";
			}

			if ($criteria ['showRefresher']) {
				$sql .= ", IF(is_refresher,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_refresher";
			}

			if ($criteria ['showSecondaryLanguage']) {
				$sql .= ', tlos.language_phrase as "secondary_language_phrase" ';
			}
			if ($criteria ['showPrimaryLanguage']) {
				$sql .= ', tlop.language_phrase as "primary_language_phrase" ';
			}
			if ($criteria ['showGotComment']) {
				$sql .= ", pt.got_comments";
			}
			if ($criteria ['showGotCurric']) {
				$sql .= ', tgotc.training_got_curriculum_phrase ';
			}

			if ($criteria ['showFunding']) {
				if ($criteria ['doCount']) {
					$sql .= ', tfund.funding_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT tfund.funding_phrase ORDER BY funding_phrase) as "funding_phrase" ';
				}
			}
			if ( $criteria['showCustom1'] ) {
				$sql .= ', tqc.custom1_phrase ';
			} // todo custom2-4
			if ( $criteria['showCreatedBy'] ) {
				$sql .= ", CONCAT(user.first_name, CONCAT(' ', user.last_name)) as created_by_user ";
			}
			if ( $criteria['showCreationDate'] ) {
				$sql .= ", DATE_FORMAT(pt.timestamp_created, '%Y-%m-%d') as created_date  ";
			}
			if ($criteria ['showGender']) {
				$sql .= ', gender ';
			}
			if ($criteria ['showAge']) {
				$sql .= ', age ';
			}
			if ($criteria ['showActive']) {
				$sql .= ', pt.active ';
			}
			if ( $criteria['showViewingLoc'] ) {
				$sql .= ', location_phrase, GROUP_CONCAT(DISTINCT location_phrase ORDER BY location_phrase) as "location_phrases" ';
			}
			if ( $criteria['showCustom1'] ) {
				$sql .= ', tqc.custom1_phrase ';
			}
			if ( $criteria['showCustom2'] ) {
				$sql .= ', tqc2.custom2_phrase';
			}
			if ( $criteria['showCustom3'] ) {
				$sql .= ', pt.custom_3';
			}
			if ( $criteria['showCustom4'] ) {
				$sql .= ', pt.custom_4';
			}
			if (($criteria['doCount'] && $criteria ['showQualPrim']) || ($criteria['doName'] && $criteria ['showQualPrim'])) {
				$sql .= ', pq.qualification_phrase ';
			}
			if (($criteria['doCount'] && $criteria ['showQualSecond']) || ($criteria['doName'] && $criteria ['showQualSecond'])) {
				$sql .= ', pqs.qualification_phrase AS qualification_secondary_phrase';
			}
			
			//TA:110 show participant column as a last
			if ($criteria ['doCount']) {
			} else {
			    $sql .= ', ptc.pcnt  ';
			}
			

			// prepare the location sub query
			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);

			//if we're doing a participant count, then LEFT JOIN with the participants
			//otherwise just select the core training info

			if ($criteria ['doCount'] || $criteria ['doName']) {
				$sql .= ' FROM (SELECT training.*, pers.person_id as "person_id", tto.training_title_phrase AS training_title, training_location.training_location_name, primary_qualification_option_id, pers.location_phrase as location_phrase,'.implode(',',$field_name).
				'         FROM training ' .
				'         LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id)' .
				'         LEFT JOIN training_location ON training.training_location_id = training_location.id ' .
				'         LEFT JOIN ('.$location_sub_query.') as l ON training_location.location_id = l.id ' .
				'         LEFT JOIN (SELECT person_id,training_id, person_to_training_viewing_loc_option.location_phrase,primary_qualification_option_id,
											person.custom_3 as person_custom_3, person.custom_4 as person_custom_4, person.custom_5 as person_custom_5
										FROM person
										JOIN person_to_training ON person_to_training.person_id = person.id
										LEFT JOIN person_to_training_viewing_loc_option ON person_to_training.viewing_location_option_id = person_to_training_viewing_loc_option.id
									) as pers ON training.id = pers.training_id WHERE training.is_deleted=0) as pt ';
			} else {
				$sql .= ' FROM (SELECT training.*, tto.training_title_phrase AS training_title,training_location.training_location_name, '.implode(',',$field_name).
				'       FROM training  ' .
				'         LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id) ' .
				'         LEFT JOIN training_location ON training.training_location_id = training_location.id ' .
				'         LEFT JOIN ('.$location_sub_query.') as l ON training_location.location_id = l.id ' .
				'  WHERE training.is_deleted=0) as pt ';
 				//$sql .= " LEFT JOIN (SELECT COUNT(id) as `pcnt`,training_id FROM person_to_training GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
                //TA:64 12/18/2015 take only persons which are not deleted
				$sql .= " LEFT JOIN (SELECT COUNT(person_to_training.id) as `pcnt`,training_id FROM person_to_training left join person on person.id=person_to_training.person_id where person.is_deleted=0 GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
			}
			if ($criteria ['doName']) {
 				//$sql .= " LEFT JOIN (SELECT COUNT(id) as `pcnt`,training_id FROM person_to_training GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
			    //TA:64 12/18/2015 take only persons which are not deleted
			    $sql .= " LEFT JOIN (SELECT COUNT(person_to_training.id) as `pcnt`,training_id FROM person_to_training left join person on person.id=person_to_training.person_id where person.is_deleted=0 GROUP BY training_id) as ptc ON ptc.training_id = pt.id ";
			}
			if (!($criteria['doCount'] || $criteria['doName']) && ($criteria['showViewingLoc'] || $criteria['person_to_training_viewing_loc_option_id'])) {
				$sql .= ' LEFT JOIN person_to_training ON person_id = person_to_training.person_id AND person_to_training.training_id = pt.id ';
				$sql .= ' LEFT JOIN person_to_training_viewing_loc_option ON person_to_training.viewing_location_option_id = person_to_training_viewing_loc_option.id ';
			}

			if ($criteria ['showOrganizer'] or $criteria ['training_organizer_id'] || $criteria ['showMechanism']  || $criteria ['mechanism_id']) {
				$sql .= '	JOIN training_organizer_option as torg ON torg.id = pt.training_organizer_option_id ';
			}

			if ($criteria ['showMechanism'] || $criteria ['mechanism_id'] && $display_training_partner) {
				$sql .= ' LEFT JOIN organizer_partners ON organizer_partners.organizer_id = torg.id';
			}

			if ($criteria ['showLevel'] || $criteria ['training_level_id']) {
				$sql .= '	JOIN training_level_option as tlev ON tlev.id = pt.training_level_option_id ';
			}

			if ($criteria ['showMethod'] || $criteria ['training_method_id']) {
				$sql .= ' JOIN training_method_option as tmeth ON tmeth.id = pt.training_method_option_id ';
			}

			if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
				$sql .= '	LEFT JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';
			}

			if ($criteria ['showTopic'] || $criteria ['training_topic_id']) {
				$sql .= '	LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';
			}

			if ($criteria ['showPrimaryLanguage'] || $criteria ['training_primary_language_option_id']) {
				$sql .= ' LEFT JOIN trainer_language_option as tlop ON tlop.id = pt.training_primary_language_option_id ';
			}

			if ($criteria ['showSecondaryLanguage'] || $criteria ['training_secondary_language_option_id']) {
				$sql .= ' LEFT JOIN trainer_language_option as tlos ON tlos.id = pt.training_secondary_language_option_id ';
			}

			if ($criteria ['showFunding'] || (intval ( $criteria ['funding_min'] ) or intval ( $criteria ['funding_max'] ))) {
				$sql .= '	LEFT JOIN (SELECT training_id, ttfo.training_funding_option_id, funding_phrase, ttfo.funding_amount FROM training_to_training_funding_option as ttfo JOIN training_funding_option as tfo ON ttfo.training_funding_option_id = tfo.id) as tfund ON tfund.training_id = pt.id ';
			}

			if ($criteria ['showGotCurric'] || $criteria ['training_got_curric_id']) {
				$sql .= '	LEFT JOIN training_got_curriculum_option as tgotc ON tgotc.id = pt.training_got_curriculum_option_id';
			}

			if ($criteria ['showCategory'] or ! empty ( $criteria ['training_category_id'] )) {
				$sql .= '
				LEFT JOIN training_category_option_to_training_title_option tcotto ON (tcotto.training_title_option_id = pt.training_title_option_id)
				LEFT JOIN training_category_option tcat ON (tcotto.training_category_option_id = tcat.id)
				';
			}
			if ( $criteria['showCustom1'] || $criteria ['custom_1_id'] ) {
				$sql .= ' LEFT JOIN training_custom_1_option as tqc ON pt.training_custom_1_option_id = tqc.id  ';
			}
			if ( $criteria['showCustom2'] || $criteria ['custom_2_id'] ) {
				$sql .= ' LEFT JOIN training_custom_2_option as tqc2 ON pt.training_custom_2_option_id = tqc2.id  ';
			}

			if ( $criteria['showCreatedBy'] || $criteria ['created_by'] ) {
				$sql .= ' LEFT JOIN user ON user.id = pt.created_by  ';
			}

			if ($criteria['showGender'] || $criteria['showAge'] || $criteria['training_gender'] || $criteria['age_min'] || $criteria['age_max']) {
				$personAlias  = ($criteria['doCount'] || $criteria['doName']) ? 'pt.person_id'  : 'person_id';

				$sql .= " LEFT JOIN person_to_training as ptt on ptt.training_id = pt.id AND $personAlias = ptt.person_id AND pt.is_deleted = 0 ";
				$sql .= ' LEFT JOIN (SELECT id as pid, gender
								,CASE WHEN birthdate  IS NULL OR birthdate = \'0000-00-00\' THEN NULL ELSE ((date_format(now(),\'%Y\') - date_format(birthdate,\'%Y\')) - (date_format(now(),\'00-%m-%d\') < date_format(birthdate,\'00-%m-%d\')) ) END as "age"
								FROM person where is_deleted = 0) as perssexage ON perssexage.pid = ptt.person_id ';
			}

			if ( ($criteria['doCount'] || $criteria['doName']) && ($criteria ['showQualPrim'] || $criteria ['showQualSecond'] || $criteria ['qualification_id']  || $criteria ['qualification_secondary_id']) ) {
				// primary qualifications
				$sql .= 'LEFT JOIN person_qualification_option as pq ON (
							(pt.primary_qualification_option_id = pq.id AND pq.parent_id IS NULL)
							OR
							pq.id = (SELECT parent_id FROM person_qualification_option WHERE id = pt.primary_qualification_option_id LIMIT 1))';

				// secondary qualifications
				$sql .= 'LEFT JOIN person_qualification_option as pqs ON (pt.primary_qualification_option_id = pqs.id AND pqs.parent_id IS NOT NULL)';
			}

			$where [] = ' pt.is_deleted=0 ';

			// restricted access?? only show trainings we have the ACL to view
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where [] = " pt.training_organizer_option_id in ($org_allowed_ids) ";
			}
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			if ($site_orgs)
				$where []= " pt.training_organizer_option_id in ($site_orgs) ";

			// criteria
			if ($criteria ['training_participants_type']) {
				if ($criteria ['training_participants_type'] == 'has_known_participants') {
					$where [] = ' pt.has_known_participants = 1 ';
				}
				if ($criteria ['training_participants_type'] == 'has_unknown_participants') {
					$where [] = ' pt.has_known_participants = 0 ';

				}
			}


			if ($this->_is_not_filter_all($criteria['training_title_option_id']) && ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0')) {
				$where [] = 'pt.training_title_option_id in (' . $this->_sql_implode($criteria ['training_title_option_id']) . ')';
			}
			if ($criteria ['training_location_id']) {
				$where [] = ' pt.training_location_id = \'' . $criteria ['training_location_id'] . '\'';
			}

			if ($this->_is_not_filter_all($criteria['training_organizer_id']) && $criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0') {
				$where [] = ' pt.training_organizer_option_id in (' . $this->_sql_implode($criteria ['training_organizer_id']) . ')';
			}

			if ($criteria ['mechanism_id'] or $criteria ['mechanism_id'] === '0' && $display_training_partner) {
				$where [] = ' organizer_partners.mechanism_id = \'' . $criteria ['mechanism_id'] . '\'';
			}

			if ($this->_is_not_filter_all($criteria['training_topic_id']) && $criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				$where [] = ' ttopic.training_topic_option_id in (' . $this->_sql_implode($criteria ['training_topic_id']) . ')';
			}

			if ($criteria ['training_level_id']) {
				$where [] = ' pt.training_level_option_id = \'' . $criteria ['training_level_id'] . '\'';
			}

			if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0') {
				$where [] = ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';
			}

			if ($criteria ['training_method_id'] or $criteria ['training_method_id'] === '0') {
				$where [] = ' tmeth.id = \'' . $criteria ['training_method_id'] . '\'';
			}

			if ($criteria ['training_primary_language_option_id'] or $criteria ['training_primary_language_option_id'] === '0') {
				$where [] = ' pt.training_primary_language_option_id = \'' . $criteria ['training_primary_language_option_id'] . '\'';
			}

			if ($criteria ['training_secondary_language_option_id'] or $criteria ['training_secondary_language_option_id'] === '0') {
				$where [] = ' pt.training_secondary_language_option_id = \'' . $criteria ['training_secondary_language_option_id'] . '\'';
			}

			if ($criteria ['province_id'] && ! empty ( $criteria ['province_id'] )) {
				$where [] = ' pt.province_id IN (' . implode ( ',', $criteria ['province_id'] ) . ')';
			}
			

			if ($criteria ['district_id'] && ! empty ( $criteria ['district_id'] )) {
				$where [] = ' pt.district_id IN (' . implode ( ',', $criteria ['district_id'] ) . ')';
			}

			if ($criteria ['region_c_id'] && ! empty ( $criteria ['region_c_id'] )) {
				$where [] = ' pt.region_c_id IN (' . implode ( ',', $criteria ['region_c_id'] ) . ')';
			}

			if ($criteria ['region_d_id'] && ! empty ( $criteria ['region_d_id'] )) {
				$where [] = ' pt.region_d_id IN (' . implode ( ',', $criteria ['region_d_id'] ) . ')';
			}

			if ($criteria ['region_e_id'] && ! empty ( $criteria ['region_e_id'] )) {
				$where [] = ' pt.region_e_id IN (' . implode ( ',', $criteria ['region_e_id'] ) . ')';
			}

			if ($criteria ['region_f_id'] && ! empty ( $criteria ['region_f_id'] )) {
				$where [] = ' pt.region_f_id IN (' . implode ( ',', $criteria ['region_f_id'] ) . ')';
			}

			if ($criteria ['region_g_id'] && ! empty ( $criteria ['region_g_id'] )) {
				$where [] = ' pt.region_g_id IN (' . implode ( ',', $criteria ['region_g_id'] ) . ')';
			}

			if ($criteria ['region_h_id'] && ! empty ( $criteria ['region_h_id'] )) {
				$where [] = ' pt.region_h_id IN (' . implode ( ',', $criteria ['region_h_id'] ) . ')';
			}

			if ($criteria ['region_i_id'] && ! empty ( $criteria ['region_i_id'] )) {
				$where [] = ' pt.region_i_id IN (' . implode ( ',', $criteria ['region_i_id'] ) . ')';
			}

			if (intval ( $criteria ['end-year'] ) and $criteria ['start-year']) {
				$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where [] = ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
			}

			if (intval ( $criteria ['funding_min'] ) or intval ( $criteria ['funding_max'] )) {
				if (intval ( $criteria ['funding_min'] ))
				$where [] = 'tfund.funding_amount >= \'' . $criteria ['funding_min'] . '\' ';
				if (intval ( $criteria ['funding_max'] ))
				$where [] = 'tfund.funding_amount <= \'' . $criteria ['funding_max'] . '\' ';
			}

			if (intval ( $criteria ['is_tot'] )) {
				$where [] = ' is_tot = ' . $criteria ['is_tot'];
			}

			if ($criteria ['funding_id'] or $criteria ['funding_id'] === '0') {
				$where [] = ' tfund.training_funding_option_id = \'' . $criteria ['funding_id'] . '\'';
			}

			if ($criteria ['training_category_id'] or $criteria ['training_category_id'] === '0') {
				$where [] = ' tcat.id = \'' . $criteria ['training_category_id'] . '\'';
			}

			if ($criteria ['training_got_curric_id'] or $criteria ['training_got_curric_id'] === '0') {
				$where [] = ' tgotc.id = \'' . $criteria ['training_got_curric_id'] . '\'';
			}

			if ($criteria ['custom_1_id'] or $criteria ['custom_1_id'] === '0') {
				$where [] = ' pt.training_custom_1_option_id = \'' . $criteria ['custom_1_id'] . '\'';
			}
			if ($criteria ['custom_2_id'] or $criteria ['custom_2_id'] === '0') {
				$where [] = ' pt.training_custom_2_option_id = \'' . $criteria ['custom_2_id'] . '\'';
			}
			if ($criteria ['custom_3_id'] or $criteria ['custom_3_id'] === '0') {
				$where [] = ' pt.custom_3 = \'' . $criteria ['custom_3_id'] . '\'';
			}
			if ($criteria ['custom_4_id'] or $criteria ['custom_4_id'] === '0') {
				$where [] = ' pt.custom_4 = \'' . $criteria ['custom_4_id'] . '\'';
			}

			if ($criteria ['created_by'] or $criteria ['created_by'] === '0') {
				$where [] = ' pt.created_by in (' . $this->_trainsmart_implode($criteria ['created_by']) . ')';
			}

			if ($criteria ['date_added']) {
				if ( isset( $criteria['date_added'][0] ) && !empty( $criteria['date_added'][0] ) ){
					$where [] = " pt.timestamp_created >= '".$criteria['date_added'][0]."' ";
				}
				if ( isset( $criteria['date_added'][1] ) && !empty( $criteria['date_added'][1] ) ){
					$where [] = " pt.timestamp_created <= '".$criteria['date_added'][1]."' ";
				}
			}
			if ($criteria ['training_gender']) {
				$where [] = " gender = '{$criteria['training_gender']}'";
			}

			if ($criteria ['age_min']) {
				$where [] = " age >= {$criteria['age_min']}";
			}

			if ($criteria ['age_max']) {
				$where [] = " age <= {$criteria['age_max']}";
			}

			if ($criteria ['person_to_training_viewing_loc_option_id']) {
				$where [] = 'person_to_training.viewing_location_option_id = ' . $criteria['person_to_training_viewing_loc_option_id'];
			}

			if (($criteria['doCount'] && $criteria ['qualification_id']) || ($criteria['doName'] && $criteria ['qualification_id'])) {
				$where [] = ' (pq.id = ' . $criteria ['qualification_id'] . ' OR pqs.parent_id = ' . $criteria ['qualification_id'] . ') ';
			}
			if (($criteria['doCount'] && $criteria ['qualification_secondary_id']) || ($criteria['doName'] && $criteria ['qualification_secondary_id'])) {
				$where [] = ' pqs.id = ' . $criteria ['qualification_secondary_id'];
			}

			if ($where)
				$sql .= ' WHERE ' . implode ( ' AND ', $where );

			if ($criteria ['doCount']) {

				$groupBy = array();

				if ($criteria ['showTrainingTitle'])     $groupBy []=  '  pt.training_title_option_id';
				if ($criteria ['showProvince'])          $groupBy []=  '  pt.province_id';
				if ($criteria ['showDistrict'])          $groupBy []=  '  pt.district_id';
				if ($criteria ['showRegionC'])           $groupBy []=  '  pt.region_c_id';
				if ($criteria ['showRegionD'])           $groupBy []=  '  pt.region_d_id';
				if ($criteria ['showRegionE'])           $groupBy []=  '  pt.region_e_id';
				if ($criteria ['showRegionF'])           $groupBy []=  '  pt.region_f_id';
				if ($criteria ['showRegionG'])           $groupBy []=  '  pt.region_g_id';
				if ($criteria ['showRegionH'])           $groupBy []=  '  pt.region_h_id';
				if ($criteria ['showRegionI'])           $groupBy []=  '  pt.region_i_id';
				if ($criteria ['showLocation'])          $groupBy []=  '  pt.training_location_id';
				if ($criteria ['showOrganizer'])         $groupBy []=  '  pt.training_organizer_option_id';
				if ($criteria ['showMechanism'] && $display_training_partner) $groupBy []=  '  organizer_partners.mechanism_id';
				if ($criteria ['showCustom1'])           $groupBy []=  '  pt.training_custom_1_option_id';
				if ($criteria ['showCustom2'])           $groupBy []=  '  pt.training_custom_2_option_id';
				if ($criteria ['showCustom3'])           $groupBy []=  '  pt.custom_3';
				if ($criteria ['showCustom4'])           $groupBy []=  '  pt.custom_4';
				if ($criteria ['showTopic'])             $groupBy []=  '  ttopic.training_topic_option_id';
				if ($criteria ['showLevel'])             $groupBy []=  '  pt.training_level_option_id';
				if ($criteria ['showPepfar'])            $groupBy []=  '  tpep.training_pepfar_categories_option_id';
				if ($criteria ['showMethod'])            $groupBy []=  '  tmeth.id';
				if ($criteria ['showTot'])               $groupBy []=  '  pt.is_tot';
				if ($criteria ['showRefresher'])         $groupBy []=  '  pt.is_refresher';
				if ($criteria ['showGotCurric'])         $groupBy []=  '  pt.training_got_curriculum_option_id';
				if ($criteria ['showPrimaryLanguage'])   $groupBy []=  '  pt.training_primary_language_option_id';
				if ($criteria ['showSecondaryLanguage']) $groupBy []=  '  pt.training_secondary_language_option_id';
				if ($criteria ['showFunding'])           $groupBy []=  '  tfund.training_funding_option_id';
				if ($criteria ['showCreatedBy'])         $groupBy []=  '  pt.created_by';
				if ($criteria ['showCreationDate'])      $groupBy []=  '  pt.timestamp_created';
				if ($criteria ['showGender'])            $groupBy []=  '  gender';
				if ($criteria ['showAge'])               $groupBy []=  '  age';
				if ($criteria ['showViewingLoc'])        $groupBy []=  '  location_phrase';
				if ($criteria ['showQualPrim'])          $groupBy []=  '  pq.qualification_phrase';
				if ($criteria ['showQualSecond'])        $groupBy []=  '  pqs.qualification_phrase';

				if ($groupBy) {
					$sql .= ' GROUP BY ' . implode(',',$groupBy);
				}

				if ($criteria['showAge'] || $criteria['showGender']) {
					$sql .= ' HAVING count(pt.person_id) > 0 ';
				}
			} else {

				$sql .= ' GROUP BY pt.id';

			}
			if ($this->view->mode == 'search') {
				$sql .= ' ORDER BY training_start_date DESC';
			}
			
			//TA:UK print $sql;
			$rowArray = $db->fetchAll ( $sql );
			
			//print_r($rowArray); output is encoded correctly from cyrillic

			if ($criteria ['doCount']) {
				$count = 0;
				foreach ( $rowArray as $row ) {
					$count += $row ['cnt'];
				}
			} else {
				$count = count ( $rowArray );
			}

			if ($this->getParam ( 'outputType' )){
			   $this->sendData ( $this->reportHeaders ( false, $rowArray ) ); //TA:110 export Excel/csv report - array inside has correct encoded cyrillic
			}

		} else {
			$count = 0;
			$rowArray = array ();
		}

		$criteria ['go'] = $this->getSanParam ( 'go' );

		//TA:UK
		$this->viewAssignEscaped ( 'results', $rowArray );

		$this->view->assign ( 'count', $count );
		$this->view->assign ( 'criteria', $criteria );

		$locations = Location::getAll();
		$this->viewAssignEscaped('locations', $locations);
		//course
		$courseArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $courseArray );
		//location
		// location drop-down
		$tlocations = TrainingLocation::selectAllLocations ($this->setting('num_location_tiers'));
		$this->viewAssignEscaped ( 'tlocations', $tlocations );
		//organizers
		// restricted access?? only show trainings we have the ACL to view
		$org_allowed_ids = allowed_organizer_access($this);
		$orgWhere = '';
		if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
			$org_allowed_ids = implode(',', $org_allowed_ids);
			$orgWhere = " id in ($org_allowed_ids) ";
		}
		// restricted access?? only show organizers that belong to this site if its a multi org site
		$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
		if ($site_orgs) {
			$orgWhere .= $orgWhere ? " AND id in ($site_orgs) " : " id in ($site_orgs) ";
		}

		$organizersArray = OptionList::suggestionList ( 'training_organizer_option', 'training_organizer_phrase', false, false, false, $orgWhere );
		$this->viewAssignEscaped ( 'organizers', $organizersArray );

		//topics
		$topicsArray = OptionList::suggestionList ( 'training_topic_option', 'training_topic_phrase', false, false, false );
		$this->viewAssignEscaped ( 'topics', $topicsArray );
		//levels
		$levelArray = OptionList::suggestionList ( 'training_level_option', 'training_level_phrase', false, false );
		$this->viewAssignEscaped ( 'levels', $levelArray );
		//pepfar
		$organizersArray = OptionList::suggestionList ( 'training_pepfar_categories_option', 'pepfar_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'pepfars', $organizersArray );
		//refresher
		if($this->setting('multi_opt_refresher_course')){
			$refresherArray = OptionList::suggestionList ( 'training_refresher_option', 'refresher_phrase_option', false, false, false );
			$this->viewAssignEscaped ( 'refresherArray', $refresherArray );
		}
		//funding
		$fundingArray = OptionList::suggestionList ( 'training_funding_option', 'funding_phrase', false, false, false );
		$this->viewAssignEscaped ( 'funding', $fundingArray );
		//category
		$categoryArray = OptionList::suggestionList ( 'training_category_option', 'training_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'category', $categoryArray );
		//primary language
		$langArray = OptionList::suggestionList ( 'trainer_language_option', 'language_phrase', false, false, false );
		$this->viewAssignEscaped ( 'language', $langArray );
		//category
		$categoryArray = OptionList::suggestionList ( 'training_category_option', 'training_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'category', $categoryArray );
		//category+titles
		$categoryTitle = MultiAssignList::getOptions ( 'training_title_option', 'training_title_phrase', 'training_category_option_to_training_title_option', 'training_category_option' );
		$this->view->assign ( 'categoryTitle', $categoryTitle );
		//training methods
		$methodTitle = OptionList::suggestionList ( 'training_method_option', 'training_method_phrase', false, false, false );
		$this->view->assign ( 'methods', $methodTitle );

		$customArray = OptionList::suggestionList ( 'training_custom_1_option', 'custom1_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom1', $customArray );
		$customArray2 = OptionList::suggestionList ( 'training_custom_2_option', 'custom2_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom2', $customArray2 );
		$customArray3 = OptionList::suggestionList ( 'training', 'custom_3', false, false, false , "custom_3 != ''" );
		$this->viewAssignEscaped ( 'custom3', $customArray3 );
		$customArray4 = OptionList::suggestionList ( 'training', 'custom_4', false, false, false , "custom_4 != ''" );
		$this->viewAssignEscaped ( 'custom4', $customArray4 );

		$createdByArray = $db->fetchAll("select id,CONCAT(first_name, CONCAT(' ', last_name)) as name from user where is_blocked = 0");
		$this->viewAssignEscaped ( 'createdBy', $createdByArray );

		$qualsArray = OptionList::suggestionList ( 'person_primary_responsibility_option', 'responsibility_phrase', false, false, false );
		$this->viewAssignEscaped ( 'responsibility_primary', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_secondary_responsibility_option', 'responsibility_phrase', false, false, false );
		$this->viewAssignEscaped ( 'responsibility_secondary', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_attend_reason_option', 'attend_reason_phrase', false, false, false );
		$this->viewAssignEscaped ( 'attend_reason', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_education_level_option', 'education_level_phrase', false, false, false);
		$this->viewAssignEscaped ( 'highest_education_level', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NULL' );
		$this->viewAssignEscaped ( 'qualifications_primary', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NOT NULL' );
		$this->viewAssignEscaped ( 'qualifications_secondary', $qualsArray );


		//mechanisms (aka training partners, organizer_partners table)
		$mechanismArray = array();
		if($display_training_partner){
			$mechanismArray = OptionList::suggestionList ( 'organizer_partners', 'mechanism_id', false, false, false, "mechanism_id != ''");
		}
		$this->viewAssignEscaped ( 'mechanisms', $mechanismArray );

		// find category based on title
		$catId = NULL;
		if ($criteria ['training_category_id']) {
			foreach ( $categoryTitle as $r ) {
				if ($r ['id'] == $criteria ['training_category_id']) {
					$catId = $r ['training_category_option_id'];
					break;
				}
			}
		}
		$this->view->assign ( 'catId', $catId );

		//got curric
		$gotCuriccArray = OptionList::suggestionList ( 'training_got_curriculum_option', 'training_got_curriculum_phrase', false, false, false );
		$this->viewAssignEscaped ( 'gotcurric', $gotCuriccArray );

		//viewing location
		$viewingLocArray = OptionList::suggestionList ( 'person_to_training_viewing_loc_option', 'location_phrase', false, false, false );
		$this->viewAssignEscaped ( 'viewing_loc', $viewingLocArray );


	}

	public function trainingUnknownAction() {

		require_once ('models/table/TrainingLocation.php');

		$this->view->assign('mode', 'unknown');

		$criteria = array ();

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = @$parts [0];
		$criteria ['start-month'] = @$parts [1];
		$criteria ['start-day'] = @$parts [2];

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		if ($this->view->mode == 'search') {
			$sql = "SELECT MAX(training_start_date) as \"start\" FROM training ";
			$rowArray = $db->fetchAll ( $sql );
			$end_default = $rowArray [0] ['start'];
			$parts = explode('-', $end_default );
			$criteria ['end-year'] = @$parts [0];
			$criteria ['end-month'] = @$parts [1];
			$criteria ['end-day'] = @$parts [2];
		} else {
			$criteria ['end-year'] = date ( 'Y' );
			$criteria ['end-month'] = date ( 'm' );
			$criteria ['end-day'] = date ( 'd' );
		}

		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );

		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);

		//  $criteria['training_title_option_id'] = $this->getSanParam('training_title_option_id'); // legacy


		// find training name from new category/title format: categoryid_titleid
		$ct_ids = $criteria ['training_category_and_title_id'] = $this->getSanParam ( 'training_category_and_title_id' );
		$criteria ['training_title_option_id'] = substr ( $ct_ids, strpos ( $ct_ids, '_' ) + 1 );

		$criteria ['training_location_id'] = $this->getSanParam ( 'training_location_id' );
		$criteria ['training_organizer_id'] = $this->getSanParam ( 'training_organizer_id' );
		//$criteria['training_organizer_option_id'] = $this->getSanParam('training_organizer_option_id');
		$criteria ['training_pepfar_id'] = $this->getSanParam ( 'training_pepfar_id' );
		$criteria ['training_method_id'] = $this->getSanParam ( 'training_method_id' );
		$criteria ['training_topic_id'] = $this->getSanParam ( 'training_topic_id' );
		$criteria ['training_level_id'] = $this->getSanParam ( 'training_level_id' );
		$criteria ['training_primary_language_option_id'] = $this->getSanParam ( 'training_primary_language_option_id' );
		$criteria ['training_secondary_language_option_id'] = $this->getSanParam ( 'training_secondary_language_option_id' );
		$criteria ['training_category_id'] = $this->getSanParam ( 'training_category_id' ); //reset(explode('_',$ct_ids));//
		$criteria ['training_got_curric_id'] = $this->getSanParam ( 'training_got_curric_id' );
		$criteria ['is_tot'] = $this->getSanParam ( 'is_tot' );
		$criteria ['funding_id'] = $this->getSanParam ( 'funding_id' );
		$criteria ['custom_1_id'] = $this->getSanParam ( 'custom_1_id' );
		$criteria ['custom_2_id'] = $this->getSanParam ( 'custom_2_id' );
		$criteria ['custom_3_id'] = $this->getSanParam ( 'custom_3_id' );
		$criteria ['custom_4_id'] = $this->getSanParam ( 'custom_4_id' );
		$criteria ['qualification_option_id'] = $this->getSanParam ( 'qualification_option_id' );
		$criteria ['age_range_option_id'] = $this->getSanParam ( 'age_range_option_id' );
		$criteria ['gender_option_id'] = $this->getSanParam ( 'gender_option_id' );

		$criteria ['funding_min'] = $this->getSanParam ( 'funding_min' );
		$criteria ['funding_max'] = $this->getSanParam ( 'funding_max' );

		$criteria ['go'] = $this->getSanParam ( 'go' );
		$criteria ['doCount'] = ($this->view->mode == 'count');
		$criteria ['showProvince'] = ($this->getSanParam ( 'showProvince' ) or ($criteria ['doCount'] and ($criteria ['province_id'] or ! empty ( $criteria ['province_id'] ))));
		$criteria ['showDistrict'] = ($this->getSanParam ( 'showDistrict' ) or ($criteria ['doCount'] and ($criteria ['district_id'] or ! empty ( $criteria ['district_id'] ))));
		$criteria ['showRegionC'] = ($this->getSanParam ( 'showRegionC' ) or ($criteria ['doCount'] and ($criteria ['region_c_id'] or ! empty ( $criteria ['region_c_id'] ))));
		$criteria ['showTrainingTitle'] = ($this->getSanParam ( 'showTrainingTitle' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0')));
		$criteria ['showLocation'] = ($this->getSanParam ( 'showLocation' ) or ($criteria ['doCount'] and $criteria ['training_location_id']));
		$criteria ['showOrganizer'] = ($this->getSanParam ( 'showOrganizer' ) or ($criteria ['doCount'] and ($criteria ['training_organizer_id'])));
		$criteria ['showPepfar'] = ($this->getSanParam ( 'showPepfar' ) or ($criteria ['doCount'] and ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0')));
		$criteria ['showMethod'] = ($this->getSanParam ( 'showMethod' ) or ($criteria ['doCount'] and ($criteria ['training_method_id'] or $criteria ['training_method_id'] === '0')));
		$criteria ['showTopic'] = ($this->getSanParam ( 'showTopic' ) or ($criteria ['doCount'] and ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')));
		$criteria ['showLevel'] = ($this->getSanParam ( 'showLevel' ) or ($criteria ['doCount'] and $criteria ['training_level_id']));
		$criteria ['showTot'] = ($this->getSanParam ( 'showTot' ) or ($criteria ['doCount'] and $criteria ['is_tot'] or $criteria ['is_tot'] === '0'));
		$criteria ['showRefresher'] = ($this->getSanParam ( 'showRefresher' ));
		$criteria ['showGotComment'] = ($this->getSanParam ( 'showGotComment' ));
		$criteria ['showPrimaryLanguage'] = ($this->getSanParam ( 'showPrimaryLanguage' ) or ($criteria ['doCount'] and $criteria ['training_primary_language_option_id'] or $criteria ['training_primary_language_option_id'] === '0'));
		$criteria ['showSecondaryLanguage'] = ($this->getSanParam ( 'showSecondaryLanguage' ) or ($criteria ['doCount'] and $criteria ['training_secondary_language_option_id'] or $criteria ['training_secondary_language_option_id'] === '0'));
		$criteria ['showFunding'] = ($this->getSanParam ( 'showFunding' ) or ($criteria ['doCount'] and $criteria ['funding_id'] or $criteria ['funding_id'] === '0' or $criteria ['funding_min'] or $criteria ['funding_max']));
		$criteria ['showCategory'] = ($this->getSanParam ( 'showCategory' ) or ($criteria ['doCount'] and $criteria ['training_category_id'] or $criteria ['training_category_id'] === '0'));
		$criteria ['showGotCurric'] = ($this->getSanParam ( 'showGotCurric' ) or ($criteria ['doCount'] and $criteria ['training_got_curric_id'] or $criteria ['training_got_curric_id'] === '0'));
		$criteria ['showCustom1'] = ($this->getSanParam ( 'showCustom1' ));
		$criteria ['showCustom2']              = ($this->getSanParam ( 'showCustom2' ));
		$criteria ['showCustom3']              = ($this->getSanParam ( 'showCustom3' ));
		$criteria ['showCustom4']              = ($this->getSanParam ( 'showCustom4' ));
		$criteria ['showEndDate'] = ($this->getSanParam('showEndDate'));
		$criteria ['showQualification'] = ($this->getSanParam('showQualification'));
		$criteria ['showAgeRange'] = ($this->getSanParam('showAgeRange'));
		$criteria ['showGender'] = ($this->getSanParam('showGender'));

		$criteria ['training_participants_type'] = $this->getSanParam ( 'training_participants_type' );

		// defaults
		if (! $criteria ['go']) {
			$criteria ['showTrainingTitle'] = 1;
		}

		if ($criteria ['go']) {

			$sql = 'SELECT ';

			if ($criteria ['doCount']) {
				$sql .= ' COUNT(pt.person_id) as "cnt" ';
			} else {

				$sql .= ' DISTINCT pt.id as "id", SUM(person_count_male + person_count_female + person_count_na) as pcnt, SUM(person_count_male) as male_pcnt, SUM(person_count_female) as female_pcnt, SUM(person_count_na) as na_pcnt, pt.training_start_date, pt.training_end_date, pt.has_known_participants  ';
			}

			if ($criteria ['showTrainingTitle']) {
				$sql .= ', training_title ';
			}

			if ($criteria ['showRegionC']) {
				$sql .= ', pt.region_c_name ';
			}
			if ($criteria ['showDistrict']) {
				$sql .= ', pt.district_name ';
			}
			if ($criteria ['showProvince']) {
				$sql .= ', pt.province_name ';
			}

			if ($criteria ['showLocation']) {
				$sql .= ', pt.training_location_name ';
			}

			if ( $criteria ['showQualification'] ) {
				$sql .= ', pqo.qualification_phrase';
				$sql .= ', ppqo.qualification_phrase as parent_qualification_phrase';
			}

			if ( $criteria ['showAgeRange'] ) {
				$sql .= ', aro.age_range_phrase';
			}

			if ($criteria ['showOrganizer']) {
				$sql .= ', torg.training_organizer_phrase ';
			}

			if ($criteria ['showLevel']) {
				$sql .= ', tlev.training_level_phrase ';
			}

			if ($criteria ['showCategory']) {
				$sql .= ', tcat.training_category_phrase ';
			}

			if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
				if ($criteria ['doCount']) {
					$sql .= ', tpep.pepfar_category_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT tpep.pepfar_category_phrase) as "pepfar_category_phrase" ';
				}
			}

			if ($criteria ['showMethod']) {
				$sql .= ', tmeth.training_method_phrase ';
			}

			if ($criteria ['showTopic']) {
				if ($criteria ['doCount']) {
					$sql .= ', ttopic.training_topic_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT ttopic.training_topic_phrase ORDER BY training_topic_phrase) AS "training_topic_phrase" ';
				}
			}

			if ($criteria ['showTot']) {
				$sql .= ", IF(is_tot,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_tot";
			}

			if ($criteria ['showRefresher']) {
				$sql .= ", IF(is_refresher,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_refresher";
			}

			if ($criteria ['showSecondaryLanguage']) {
				$sql .= ', tlos.language_phrase as "secondary_language_phrase" ';
			}
			if ($criteria ['showPrimaryLanguage']) {
				$sql .= ', tlop.language_phrase as "primary_language_phrase" ';
			}
			if ($criteria ['showGotComment']) {
				$sql .= ", pt.got_comments";
			}
			if ($criteria ['showGotCurric']) {
				$sql .= ', tgotc.training_got_curriculum_phrase ';
			}

			if ($criteria ['showFunding']) {
				if ($criteria ['doCount']) {
					$sql .= ', tfund.funding_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT tfund.funding_phrase ORDER BY funding_phrase) as "funding_phrase" ';
				}
			}
			if ( $criteria['showCustom1'] ) {
				$sql .= ', tqc.custom1_phrase ';
			}
			if ( $criteria['showCustom2'] ) {
				$sql .= ', tqc2.custom2_phrase ';
			}
			if ( $criteria['showCustom3'] ) {
				$sql .= ', pt.custom_3 ';
			}
			if ( $criteria['showCustom4'] ) {
				$sql .= ', pt.custom_4 ';
			}

			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);

			//if we're doing a participant count, then LEFT JOIN with the participants
			//otherwise just select the core training info


			if ($criteria ['doCount']) {
				$sql .= ' FROM (SELECT training.*, pers.person_id as "person_id", tto.training_title_phrase AS training_title, training_location.training_location_name, '.implode(',',$field_name).
				'       FROM training ' .
				'         LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id)' .
				'         LEFT JOIN training_location ON training.training_location_id = training_location.id ' .
				'         LEFT JOIN ('.$location_sub_query.') as l ON training_location.location_id = l.id ' .
				'         LEFT JOIN (SELECT person_id,training_id FROM person JOIN person_to_training ON person_to_training.person_id = person.id) as pers ON training.id = pers.training_id WHERE training.is_deleted=0  AND training.has_known_participants = 0) as pt ';
			} else {
				$sql .= ' FROM (SELECT training.*, tto.training_title_phrase AS training_title,training_location.training_location_name, '.implode(',',$field_name).
				'       FROM training  ' .
				'         LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id) ' .
				'         LEFT JOIN training_location ON training.training_location_id = training_location.id ' .
				'         LEFT JOIN ('.$location_sub_query.') as l ON training_location.location_id = l.id ' .
				'  WHERE training.is_deleted=0 AND training.has_known_participants = 0) as pt ';

				$sql .= ' LEFT JOIN training_to_person_qualification_option as ttpqo ON ttpqo.training_id = pt.id ';
			}

			if ($criteria ['showQualification'] ) {
				$sql .= ' LEFT JOIN person_qualification_option as pqo ON ttpqo.person_qualification_option_id = pqo.id';
				$sql .= ' LEFT JOIN person_qualification_option as ppqo ON pqo.parent_id = ppqo.id';
			}

			if ($criteria ['showAgeRange'] ) {
				$sql .= ' LEFT JOIN age_range_option as aro ON ttpqo.age_range_option_id = aro.id';
			}

			if ($criteria ['showOrganizer'] or $criteria ['training_organizer_id']) {
				$sql .= ' LEFT JOIN training_organizer_option as torg ON torg.id = pt.training_organizer_option_id ';
			}
			
			if ($criteria ['showLevel'] || $criteria ['training_level_id']) {
				$sql .= ' LEFT JOIN training_level_option as tlev ON tlev.id = pt.training_level_option_id ';
			}

			if ($criteria ['showMethod'] || $criteria ['training_method_id']) {
				$sql .= ' LEFT JOIN training_method_option as tmeth ON tmeth.id = pt.training_method_option_id ';
			}

			if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
				$sql .= ' LEFT JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';
			}

			if ($criteria ['showTopic'] || $criteria ['training_topic_id']) {
				$sql .= ' LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';
			}

			if ($criteria ['showPrimaryLanguage'] || $criteria ['training_primary_language_option_id']) {
				$sql .= ' LEFT JOIN trainer_language_option as tlop ON tlop.id = pt.training_primary_language_option_id ';
			}

			if ($criteria ['showSecondaryLanguage'] || $criteria ['training_secondary_language_option_id']) {
				$sql .= ' LEFT JOIN trainer_language_option as tlos ON tlos.id = pt.training_secondary_language_option_id ';
			}

			if ($criteria ['showFunding'] || (intval ( $criteria ['funding_min'] ) or intval ( $criteria ['funding_max'] ))) {
				$sql .= ' LEFT JOIN (SELECT training_id, ttfo.training_funding_option_id, funding_phrase, ttfo.funding_amount FROM training_to_training_funding_option as ttfo JOIN training_funding_option as tfo ON ttfo.training_funding_option_id = tfo.id) as tfund ON tfund.training_id = pt.id ';
			}

			if ($criteria ['showGotCurric'] || $criteria ['training_got_curric_id']) {
				$sql .= ' LEFT JOIN training_got_curriculum_option as tgotc ON tgotc.id = pt.training_got_curriculum_option_id';
			}

			if ($criteria ['showCategory'] or ! empty ( $criteria ['training_category_id'] )) {
				$sql .= '
				LEFT JOIN training_category_option_to_training_title_option tcotto ON (tcotto.training_title_option_id = pt.training_title_option_id)
				LEFT JOIN training_category_option tcat ON (tcotto.training_category_option_id = tcat.id)
				';
			}
			if ( $criteria['showCustom1'] ) {
				$sql .= ' LEFT JOIN training_custom_1_option as tqc ON pt.training_custom_1_option_id = tqc.id  ';
			}
			if ( $criteria['showCustom2'] ) {
				$sql .= ' LEFT JOIN training_custom_2_option as tqc2 ON pt.training_custom_2_option_id = tqc2.id  ';
			}

			$where = array ();
			$where [] = ' pt.is_deleted=0 ';

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where []= " pt.training_organizer_option_id in ($org_allowed_ids) ";
			}


			if ( $criteria['qualification_option_id']) {
				$where []= ' ttpqo.person_qualification_option_id = '.$criteria['qualification_option_id'];
			}
			if ( $criteria['age_range_option_id']) {
				$where []= ' ttpqo.age_range_option_id = '.$criteria['age_range_option_id'];
			}

			if ($criteria ['training_participants_type']) {
				if ($criteria ['training_participants_type'] == 'has_known_participants') {
					$where [] = ' pt.has_known_participants = 1 ';
				}
				if ($criteria ['training_participants_type'] == 'has_unknown_participants') {
					$where [] = ' pt.has_known_participants = 0 ';

				}
			}

			if ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0') {
				$where [] = 'pt.training_title_option_id = ' . $criteria ['training_title_option_id'];
			}

			if ($criteria ['training_location_id']) {
				$where [] = ' pt.training_location_id = \'' . $criteria ['training_location_id'] . '\'';
			}

			if ($criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0') {
				$where [] = ' pt.training_organizer_option_id = \'' . $criteria ['training_organizer_id'] . '\'';
			}

			if ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				$where [] = ' ttopic.training_topic_option_id = \'' . $criteria ['training_topic_id'] . '\'';
			}

			if ($criteria ['training_level_id']) {
				$where [] = ' pt.training_level_option_id = \'' . $criteria ['training_level_id'] . '\'';
			}

			if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0') {
				$where [] = ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';
			}

			if ($criteria ['training_method_id'] or $criteria ['training_method_id'] === '0') {
				$where [] = ' tmeth.id = \'' . $criteria ['training_method_id'] . '\'';
			}

			if ($criteria ['training_primary_language_option_id'] or $criteria ['training_primary_language_option_id'] === '0') {
				$where [] = ' pt.training_primary_language_option_id = \'' . $criteria ['training_primary_language_option_id'] . '\'';
			}

			if ($criteria ['training_secondary_language_option_id'] or $criteria ['training_secondary_language_option_id'] === '0') {
				$where [] = ' pt.training_secondary_language_option_id = \'' . $criteria ['training_secondary_language_option_id'] . '\'';
			}

			if (intval ( $criteria ['end-year'] ) and $criteria ['start-year']) {
				$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where [] = ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
			}

			if (intval ( $criteria ['funding_min'] ) or intval ( $criteria ['funding_max'] )) {
				if (intval ( $criteria ['funding_min'] ))
				$where [] = 'tfund.funding_amount >= \'' . $criteria ['funding_min'] . '\' ';
				if (intval ( $criteria ['funding_max'] ))
				$where [] = 'tfund.funding_amount <= \'' . $criteria ['funding_max'] . '\' ';
			}

			if (intval ( $criteria ['is_tot'] )) {
				$where [] = ' is_tot = ' . $criteria ['is_tot'];
			}

			if ($criteria ['funding_id'] or $criteria ['funding_id'] === '0') {
				$where [] = ' tfund.training_funding_option_id = \'' . $criteria ['funding_id'] . '\'';
			}

			if ($criteria ['training_category_id'] or $criteria ['training_category_id'] === '0') {
				$where [] = ' tcat.id = \'' . $criteria ['training_category_id'] . '\'';
			}

			if ($criteria ['training_got_curric_id'] or $criteria ['training_got_curric_id'] === '0') {
				$where [] = ' tgotc.id = \'' . $criteria ['training_got_curric_id'] . '\'';
			}

			if ($criteria ['custom_1_id'] or $criteria ['custom_1_id'] === '0') {
				$where [] = ' pt.training_custom_1_option_id = \'' . $criteria ['custom_1_id'] . '\'';
			}
			if ($criteria ['custom_2_id'] or $criteria ['custom_2_id'] === '0') {
				$where [] = ' pt.training_custom_2_option_id = \'' . $criteria ['custom_2_id'] . '\'';
			}
			if ($criteria ['custom_3_id'] or $criteria ['custom_3_id'] === '0') {
				$where [] = ' pt.custom_3 = \'' . $criteria ['custom_3_id'] . '\'';
			}
			if ($criteria ['custom_4_id'] or $criteria ['custom_4_id'] === '0') {
				$where [] = ' pt.custom_4 = \'' . $criteria ['custom_4_id'] . '\'';
			}
			if ($where)
			$sql .= ' WHERE ' . implode ( ' AND ', $where );

			if ($criteria ['doCount']) {

				$groupBy = array();

				if ($criteria ['showTrainingTitle'])     $groupBy [] = 'pt.training_title_option_id';
				if ($criteria ['showProvince'])          $groupBy [] = 'pt.province_id';
				if ($criteria ['showDistrict'])          $groupBy [] = 'pt.district_id';
				if ($criteria ['showRegionC'])           $groupBy [] = 'pt.region_c_id';
				if ($criteria ['showLocation'])          $groupBy [] = 'pt.training_location_id';
				if ($criteria ['showOrganizer'])         $groupBy [] = 'pt.training_organizer_option_id';
				if ($criteria ['showCustom1'])           $groupBy [] = 'pt.training_custom_1_option_id';
				if ($criteria ['showCustom2'])           $groupBy [] = 'pt.training_custom_2_option_id';
				if ($criteria ['showCustom3'])           $groupBy [] = 'pt.custom_3';
				if ($criteria ['showCustom4'])           $groupBy [] = 'pt.custom_4';
				if ($criteria ['showTopic'])             $groupBy [] = 'ttopic.training_topic_option_id';
				if ($criteria ['showLevel'])             $groupBy [] = 'pt.training_level_option_id';
				if ($criteria ['showPepfar'])            $groupBy [] = 'tpep.training_pepfar_categories_option_id';
				if ($criteria ['showMethod'])            $groupBy [] = 'tmeth.id';
				if ($criteria ['showTot'])               $groupBy [] = 'pt.is_tot';
				if ($criteria ['showRefresher'])         $groupBy [] = 'pt.is_refresher';
				if ($criteria ['showGotCurric'])         $groupBy [] = 'pt.training_got_curriculum_option_id';
				if ($criteria ['showPrimaryLanguage'])   $groupBy [] = 'pt.training_primary_language_option_id';
				if ($criteria ['showSecondaryLanguage']) $groupBy [] = 'pt.training_secondary_language_option_id';
				if ($criteria ['showFunding'])           $groupBy [] = 'tfund.training_funding_option_id';

				if ($groupBy) {
					$sql .= ' GROUP BY ' . implode(',',$groupBy);
				}

			} else {
				$groupBy = array();
				$groupBy []= 'pt.id';

				if ($criteria ['showQualification']) $groupBy []= ' ttpqo.person_qualification_option_id';
				if ($criteria ['showAgeRange']) $groupBy []= ' ttpqo.age_range_option_id';

				$sql .= ' GROUP BY '.implode(',',$groupBy);

			}

			if ($this->view->mode == 'search') {
				$sql .= ' ORDER BY training_start_date DESC';
			}

			$rowArray = $db->fetchAll ( $sql );

			if ($criteria ['doCount']) {
				$count = 0;
				foreach ( $rowArray as $row ) {
					$count += $row ['cnt'];
				}
			} else {
				$count = count ( $rowArray );
			}

			if ($this->getParam ( 'outputType' ))
			$this->sendData ( $this->reportHeaders ( false, $rowArray ) );

		} else {
			$count = 0;
			$rowArray = array ();
		}

		$criteria ['go'] = $this->getSanParam ( 'go' );

		$this->viewAssignEscaped ( 'results', $rowArray );
		$this->view->assign ( 'count', $count );
		$this->view->assign ( 'criteria', $criteria );

		$locations = Location::getAll();
		$this->viewAssignEscaped('locations', $locations);
		//course
		//$courseArray = Course::suggestionList(false,10000);
		//$this->viewAssignEscaped('courses',$courseArray);
		//location
		// location drop-down
		$tlocations = TrainingLocation::selectAllLocations ($this->setting('num_location_tiers'));
		$this->viewAssignEscaped ( 'tlocations', $tlocations );
		//organizers
		// restricted access?? only show trainings we have the ACL to view
		require_once('views/helpers/TrainingViewHelper.php');
		$org_allowed_ids = allowed_organizer_access($this);
		$orgWhere = '';
		if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
			$org_allowed_ids = implode(',', $org_allowed_ids);
			$orgWhere .= " id in ($org_allowed_ids) ";
		}
		// restricted access?? only show organizers that belong to this site if its a multi org site
		$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
		if ($site_orgs) {
			$orgWhere .= $orgWhere ? " AND id in ($site_orgs) " : " id in ($site_orgs) ";
		}

		$organizersArray = OptionList::suggestionList ( 'training_organizer_option', 'training_organizer_phrase', false, false, false, $orgWhere );
		$this->viewAssignEscaped ( 'organizers', $organizersArray );
		//topics
		$topicsArray = OptionList::suggestionList ( 'training_topic_option', 'training_topic_phrase', false, false, false );
		$this->viewAssignEscaped ( 'topics', $topicsArray );
		//levels
		$levelArray = OptionList::suggestionList ( 'training_level_option', 'training_level_phrase', false, false );
		$this->viewAssignEscaped ( 'levels', $levelArray );
		//pepfar
		$organizersArray = OptionList::suggestionList ( 'training_pepfar_categories_option', 'pepfar_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'pepfars', $organizersArray );
		//funding
		$fundingArray = OptionList::suggestionList ( 'training_funding_option', 'funding_phrase', false, false, false );
		$this->viewAssignEscaped ( 'funding', $fundingArray );
		//category
		$categoryArray = OptionList::suggestionList ( 'training_category_option', 'training_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'category', $categoryArray );
		//primary language
		$langArray = OptionList::suggestionList ( 'trainer_language_option', 'language_phrase', false, false, false );
		$this->viewAssignEscaped ( 'language', $langArray );
		//category
		$categoryArray = OptionList::suggestionList ( 'training_category_option', 'training_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'category', $categoryArray );
		//category+titles
		$categoryTitle = MultiAssignList::getOptions ( 'training_title_option', 'training_title_phrase', 'training_category_option_to_training_title_option', 'training_category_option' );
		$this->view->assign ( 'categoryTitle', $categoryTitle );
		//training methods
		$methodTitle = OptionList::suggestionList ( 'training_method_option', 'training_method_phrase', false, false, false );
		$this->view->assign ( 'methods', $methodTitle );

		// custom fields
		$customArray = OptionList::suggestionList ( 'training_custom_1_option', 'custom1_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom1', $customArray );
		$customArray2 = OptionList::suggestionList ( 'training_custom_2_option', 'custom2_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom2', $customArray2 );
		$customArray3 = OptionList::suggestionList ( 'training', 'custom_3', false, false, false, "custom_3 != ''");
		$this->viewAssignEscaped ( 'custom3', $customArray3 );
		$customArray4 = OptionList::suggestionList ( 'training', 'custom_4', false, false, false, "custom_4 != ''" );
		$this->viewAssignEscaped ( 'custom4', $customArray4 );

		$customArray = OptionList::suggestionList ( 'age_range_option', 'age_range_phrase', false, false, false );
		$this->viewAssignEscaped ( 'age_range', $customArray );

		//$customArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NULL');
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false, array ('0 AS is_default', 'child.is_default' ) );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );

		$this->viewAssignEscaped ( 'gender', array(
		array('id'=>1,'name'=>t('Unknown')),
		array('id'=>2,'name'=>t('Female')),
		array('id'=>3,'name'=>t('Male')) ));

		// find category based on title
		$catId = NULL;
		if ($criteria ['training_category_id']) {
			foreach ( $categoryTitle as $r ) {
				if ($r ['id'] == $criteria ['training_category_id']) {
					$catId = $r ['training_category_option_id'];
					break;
				}
			}
		}
		$this->view->assign ( 'catId', $catId );

		//got curric
		$gotCuriccArray = OptionList::suggestionList ( 'training_got_curriculum_option', 'training_got_curriculum_phrase', false, false, false );
		$this->viewAssignEscaped ( 'gotcurric', $gotCuriccArray );

	}

	public function budgetCodeAction()
	{
		require_once ('views/helpers/FormHelper.php');
		require_once ('models/table/TrainingLocation.php');
		require_once ('views/helpers/DropDown.php');
		require_once ('views/helpers/Location.php');
		$criteria = $this->getAllParams();

		if ($criteria ['go']) {

			list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
			list($field_name,$location_sub_query) = Location::subquery($this->setting('num_location_tiers'), $location_tier, $location_id, true);

			$sql = 'SELECT  training_id, training_title_phrase, training_start_date, budget_code_phrase, training_location_name, total_participants, num_participants,
							l.'.implode(', l.',$field_name).',
							num_participants / total_participants * 100 as percentage
						FROM person_to_training
						LEFT JOIN training ON training_id = training.id
						LEFT JOIN training_title_option tto ON training.training_title_option_id = tto.id
						LEFT JOIN training_location ON training_location.id = training.training_location_id
						LEFT JOIN ('.$location_sub_query.') AS l ON training_location.location_id = l.id
						LEFT JOIN person_to_training_budget_option budget on person_to_training.budget_code_option_id = budget.id
						LEFT JOIN (SELECT COUNT(ptt.id) as total_participants, ptt.training_id as tid FROM person_to_training ptt GROUP BY ptt.training_id) stat1 ON stat1.tid = person_to_training.training_id
						LEFT JOIN (SELECT COUNT(ptt.id) as num_participants, ptt.training_id as tid, budget_code_option_id as budget_code_id FROM person_to_training ptt GROUP BY budget_code_option_id,ptt.training_id) stat2 on stat2.tid = person_to_training.training_id and stat2.budget_code_id = person_to_training.budget_code_option_id
					';

			$where = array( 'training.is_deleted = 0' );

			if ($locWhere = $this->getLocationCriteriaWhereClause($criteria)) {
				$where [] = $locWhere;
			}
			if ($criteria ['training_location_id']) {
				$where [] = 'training.training_location_id = ' . $criteria['training_location_id'];
			}
			if ($criteria ['budget_code_option_id']) {
				$where [] = 'budget.id = ' . $criteria['budget_code_option_id'];
			}
			if ($criteria ['training_title_option_id']) {
				$where [] = 'tto.id = ' . $criteria['training_title_option_id'];
			}
			if ($criteria ['start_date']) {
				$where [] = 'training.training_start_date >= "' . $this->_date_to_sql( $criteria['start_date'] ). '"';
			}
			if ($criteria ['end_date']) {
				$where [] = 'training.training_start_date <= "' . $this->_date_to_sql( $criteria['end_date']) . ' 23:59:59"';
			}

			if ($where)
				$sql .= ' WHERE ' . implode(' AND ', $where);

			$sql .= ' GROUP BY budget_code_option_id, training_id ';
			$sql .= ' ORDER BY training_id, budget_code_option_id ';
			$db = $this->dbfunc();
			$rowArray = $db->fetchAll($sql);

			$this->viewAssignEscaped ( 'results', $rowArray );
			$this->view->assign ( 'count' , count($rowArray) );

			// output csv if necessary
			if ($this->getParam ( 'outputType' ))
				$this->sendData ( $this->reportHeaders ( false, $rowArray ) );


		} // fi run report

		// assign form drop downs
		$this->viewAssignEscaped ( 'criteria',   $criteria );
		$this->viewAssignEscaped ( 'locations',  Location::getAll());
		$this->viewAssignEscaped ( 'tlocations', TrainingLocation::selectAllLocations ($this->setting('num_location_tiers')));
		$this->view->assign ( 'partners',    DropDown::generateHtml ( 'person_to_training_budget_option', 'budget_code_phrase',    $criteria['budget_code_option_id'], false, $this->view->viewonly, false ) ); //table, col, selected_value
		$this->view->assign ( 'titles',      DropDown::generateHtml ( 'training_title_option',            'training_title_phrase', $criteria['training_title_option_id'], false, $this->view->viewonly, false ) ); //table, col, selected_value
	}

	public function trainersByNameAction() {
		$this->view->assign('is_trainers', true);
		return $this->peopleReport();

	}

	
	public function peopleReport() {
		require_once ('views/helpers/DropDown.php');
		$criteria = array ();

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = '0000-00-00';
		if ($rowArray and $rowArray [0] ['start'])
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = $parts [0];
		$criteria ['start-month'] = $parts [1];
		$criteria ['start-day'] = $parts [2];

		$criteria ['showAge'] = $this->getSanParam ( 'showAge' );
		$criteria ['age_min'] = $this->getSanParam ( 'age_min' );
		$criteria ['age_max'] = $this->getSanParam ( 'age_max' );

		
		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		$criteria ['end-year'] = date ( 'Y' );
		$criteria ['end-month'] = date ( 'm' );
		$criteria ['end-day'] = date ( 'd' );
		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );

		//TA:33 list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);

		$criteria ['training_gender']              = $this->getSanParam ( 'training_gender' );
		$criteria ['training_active']              = $this->getSanParam ( 'training_active' );
		$criteria ['concatNames']                  = $this->getSanParam ( 'concatNames' );
		$criteria ['training_title_option_id']     = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['training_title_id']            = $this->getSanParam ( 'training_title_id' );
		$criteria ['training_pepfar_id']           = $this->getSanParam ( 'training_pepfar_id' );
		$criteria ['training_topic_id']            = $this->getSanParam ( 'training_topic_id' );
		$criteria ['qualification_id']             = $this->getSanParam ( 'qualification_id' );
		$criteria ['qualification_secondary_id']   = $this->getSanParam ( 'qualification_secondary_id' );
		$criteria ['facilityInput']                = $this->getSanParam ( 'facilityInput' );
		$criteria ['is_tot']                       = $this->getSanParam ( 'is_tot' );
		$criteria ['training_organizer_id']        = $this->getSanParam ( 'training_organizer_id' );
		$criteria ['training_organizer_option_id'] = $this->getSanParam ( 'training_organizer_option_id' );
		$criteria ['training_method_option_id'] = $this->getSanParam ( 'training_method_option_id' );//TA:#514
		$criteria ['people_funding_id'] = $this->getSanParam ( 'people_funding_id' );//TA:#529
		$criteria ['funding_id']                   = $this->getSanParam ( 'funding_id' );
		$criteria ['custom_1_id']                  = $this->getSanParam ( 'custom_1_id' );
		$criteria ['custom_2_id']                  = $this->getSanParam ( 'custom_2_id' );
		$criteria ['custom_3_id']                  = $this->getSanParam ( 'custom_3_id' );
		$criteria ['custom_4_id']                  = $this->getSanParam ( 'custom_4_id' );
		$criteria ['custom_5_id']                  = $this->getSanParam ( 'custom_5_id' );
		$criteria ['distinctCount']                = $this->getSanParam ( 'distinctCount' );
		$criteria ['person_to_training_viewing_loc_option_id'] = $this->getSanParam('person_to_training_viewing_loc_option_id');
		if ($this->view->isScoreReport) {
			$criteria ['score_min'] = (is_numeric ( trim ( $this->getSanParam ( 'score_min' ) ) )) ? trim ( $this->getSanParam ( 'score_min' ) ) : '';
			$criteria ['score_percent_min'] = (is_numeric ( trim ( $this->getSanParam ( 'score_percent_min' ) ) )) ? trim ( $this->getSanParam ( 'score_percent_min' ) ) : '';
		}
		
		//TA:33 fix bug, get http parameter
		$criteria ['province_id'] = $this->getSanParam ( 'province_id' );
		$arr_dist = $this->getSanParam ( 'district_id' );
		// level 2 location has parameter as [parent_location_id]_[location_id], we need to take only location_ids
		for($i=0;$i<sizeof($arr_dist); $i++){
			if ( strstr($arr_dist[$i], '_') !== false ) {
				$parts = explode('_',$arr_dist[$i]);
				$arr_dist[$i] = $parts[1];
			}
		}
		$criteria ['district_id'] = $arr_dist;
		
		$criteria ['doCount']           = ($this->view->mode == 'count');
		$criteria ['showProvince']      = ($this->getSanParam ( 'showProvince' ) or ($criteria ['doCount'] and ($criteria ['province_id'] or ! empty ( $criteria ['province_id'] ))));
		$criteria ['showDistrict']      = ($this->getSanParam ( 'showDistrict' ) or ($criteria ['doCount'] and ($criteria ['district_id'] or ! empty ( $criteria ['district_id'] ))));
		$criteria ['showRegionC']       = ($this->getSanParam ( 'showRegionC' ) or ($criteria ['doCount'] and ($criteria ['region_c_id'] or ! empty ( $criteria ['region_c_id'] ))));
		$criteria ['showRegionD']       = ($this->getSanParam ( 'showRegionD' ) or ($criteria ['doCount'] and ($criteria ['region_d_id'] or ! empty ( $criteria ['region_d_id'] ))));
		$criteria ['showRegionE']       = ($this->getSanParam ( 'showRegionE' ) or ($criteria ['doCount'] and ($criteria ['region_e_id'] or ! empty ( $criteria ['region_e_id'] ))));
		$criteria ['showRegionF']       = ($this->getSanParam ( 'showRegionF' ) or ($criteria ['doCount'] and ($criteria ['region_f_id'] or ! empty ( $criteria ['region_f_id'] ))));
		$criteria ['showRegionG']       = ($this->getSanParam ( 'showRegionG' ) or ($criteria ['doCount'] and ($criteria ['region_g_id'] or ! empty ( $criteria ['region_g_id'] ))));
		$criteria ['showRegionH']       = ($this->getSanParam ( 'showRegionH' ) or ($criteria ['doCount'] and ($criteria ['region_h_id'] or ! empty ( $criteria ['region_h_id'] ))));
		$criteria ['showRegionI']       = ($this->getSanParam ( 'showRegionI' ) or ($criteria ['doCount'] and ($criteria ['region_i_id'] or ! empty ( $criteria ['region_i_id'] ))));
		$criteria ['showTrainingTitle'] = ($this->getSanParam ( 'showTrainingTitle' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0' or $criteria ['training_title_id'])));
		$criteria ['showPepfar']        = ($this->getSanParam ( 'showPepfar' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_pepfar_id'] === '0')));
		$criteria ['showQualification'] = false; // ($this->getSanParam('showQualification') OR ($criteria['doCount']  and ($criteria['qualification_id'] or $criteria['qualification_id'] === '0') ));
		$criteria ['showTopic']         = ($this->getSanParam ( 'showTopic' ) or ($criteria ['doCount'] and ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')));
		$criteria ['showFacility']      = ($this->getSanParam ( 'showFacility' ) or ($criteria ['doCount'] and $criteria ['facilityInput']));
		$criteria ['showGender']        = ($this->getSanParam ( 'showGender' ) or ($criteria ['doCount'] and $criteria ['training_gender']));
		$criteria ['showActive']        = ($this->getSanParam ( 'showActive' ) or ($criteria ['doCount'] and $criteria ['training_active']));
		$criteria ['showSuffix']        = ($this->getSanParam ( 'showSuffix' ));
		$criteria ['showEmail']         = ($this->getSanParam ( 'showEmail' ));
		$criteria ['showPhone']         = ($this->getSanParam ( 'showPhone' ));
		$criteria ['show_score_increase']         = ($this->getSanParam ( 'show_score_increase' ));//TA:#313
		$criteria ['show_other_scores']         = ($this->getSanParam ( 'show_other_scores' ));//TA:#313
		$criteria ['showTot']           = ($this->getSanParam ( 'showTot' ) or ($criteria ['doCount'] and $criteria ['is_tot'] !== '' or $criteria ['is_tot'] === '0'));
		$criteria ['showOrganizer']     = ($this->getSanParam ( 'showOrganizer' ) or ($criteria ['doCount'] and ($criteria ['training_organizer_option_id'])));
		$criteria ['showMethod']     = ($this->getSanParam ( 'showMethod' ) or ($criteria ['doCount'] and ($criteria ['training_method_option_id'])));//TA:#514
		$criteria ['showFunding']       = ($this->getSanParam ( 'showFunding' ) or ($criteria ['doCount'] and $criteria ['funding_id'] or $criteria ['funding_id'] === '0'));
		$criteria ['showQualPrim']      = ($this->getSanParam ( 'showQualPrim' ) or ($criteria ['doCount'] and ($criteria ['qualification_id'] or $criteria ['qualification_id'] === '0')));
		$criteria ['showQualSecond']    = ($this->getSanParam ( 'showQualSecond' ) or ($criteria ['doCount'] and ($criteria ['qualification_secondary_id'] or $criteria ['qualification_secondary_id'] === '0')));
		$criteria ['showCustom1']       = ($this->getSanParam ( 'showCustom1' ));
		$criteria ['showCustom2']       = ($this->getSanParam ( 'showCustom2' ));
		$criteria ['showCustom3']       = ($this->getSanParam ( 'showCustom3' ));
		$criteria ['showCustom4']       = ($this->getSanParam ( 'showCustom4' ));
		$criteria ['showCustom5']       = ($this->getSanParam ( 'showCustom5' ));
		$criteria ['showRespPrim']      = ($this->getSanParam ( 'showRespPrim' ));
		$criteria ['showRespSecond']    = ($this->getSanParam ( 'showRespSecond' ));
		$criteria ['showHighestEd']     = ($this->getSanParam ( 'showHighestEd' ));
		$criteria ['showReason']        = ($this->getSanParam ( 'showReason' ));
		$criteria ['showViewingLoc']    = $this->getSanParam ( 'showViewingLoc' );
		$criteria ['showPeopleFund']    = $this->getSanParam ( 'showPeopleFund' );//TA:#529

		$criteria ['primary_responsibility_option_id'] = $this->getSanParam ( 'primary_responsibility_option_id' );
		$criteria ['secondary_responsibility_option_id'] = $this->getSanParam ( 'secondary_responsibility_option_id' );

		$criteria ['highest_edu_level_option_id'] = $this->getSanParam ( 'highest_edu_level_option_id' );
		$criteria ['attend_reason_option_id'] = $this->getSanParam ( 'attend_reason_option_id' );


		$criteria ['go'] = $this->getSanParam ( 'go' );
		if ($criteria ['go']) {

			$sql = 'SELECT ';

/*			
			if ($criteria ['doCount']) {
				$distinct = ($criteria ['distinctCount']) ? 'DISTINCT ' : '';
				$sql .= ' COUNT(' . $distinct . 'person_id) as "cnt" ';
			} else {
				if ($criteria ['concatNames'])
				$sql .= ' DISTINCT person_id as "id", CONCAT(first_name, ' . "' '" . ',last_name, ' . "' '" . ', IFNULL(suffix_phrase, ' . "' '" . ')) as "name", IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, pt.training_start_date  ';
				else
				$sql .= ' DISTINCT person_id as "id", IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, last_name, first_name, middle_name, pt.training_start_date  ';
			}
*/

			if ($criteria ['doCount']) {
			    $distinct = ($criteria ['distinctCount']) ? 'DISTINCT ' : '';
			    $sql .= ' COUNT(' . $distinct . 'person_id) as "cnt" ';
			}
			else {
			    if ($criteria ['concatNames']) {
			        $sql .= ' DISTINCT person_id as "id", CONCAT(first_name, ' . "' '" . ',last_name, ' . "' '" . ', IFNULL(suffix_phrase, ' . "' '" . '))
             "name", IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase ';
			    }
			    else {
			        $sql .= ' DISTINCT person_id as "id", IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, last_name, first_name, middle_name ';
			    }
			}
				
			if ($criteria ['distinctCount']){
			    // $sql .= ' , pt.training_title ';
			}
			else {
			    $sql .= ' , pt.training_start_date ';
			}
			
			if ($criteria ['showPhone']) {
				$sql .= ", CASE WHEN (pt.phone_work IS NULL OR pt.phone_work = '') THEN NULL ELSE pt.phone_work END as \"phone_work\", CASE WHEN (pt.phone_home IS NULL OR pt.phone_home = '') THEN NULL ELSE pt.phone_home END as \"phone_home\", CASE WHEN (pt.phone_mobile IS NULL OR pt.phone_mobile = '') THEN NULL ELSE pt.phone_mobile END as \"phone_mobile\" ";
			}
			if ($criteria ['showEmail']) {
				$sql .= ', pt.email ';
			}
			if ($criteria ['showAge']) {
				$sql .= ', pt.age ';
			}
			if ($criteria ['showGender']) {
				$sql .= ', pt.gender ';
			}
			if ($criteria ['showActive']) {
				$sql .= ', pt.active ';
			}
			if ($criteria ['showTrainingTitle']) {
				$sql .= ', pt.training_title ';
			}
			//TA:#529
			if ($criteria ['showPeopleFund'] || $criteria ['people_funding_id'][0]) {
			   // $sql .= ', pt.funding_phrase, pt.funding_amount ';
			    $sql .= ', pt.people_funding_option_id, GROUP_CONCAT( DISTINCT CONCAT(pt.funding_phrase, CONCAT (CONCAT("(", pt.funding_amount), ")"))  ORDER BY pt.funding_phrase SEPARATOR ", ") as people_funding ';
			}
			if ($criteria ['showDistrict']) {
				$sql .= ', pt.district_name ';
			}
			if ($criteria ['showProvince']) {
				$sql .= ', pt.province_name ';
			}

			if ($criteria ['showRegionC']) {
				$sql .= ', pt.region_c_name ';
			}
			if ($criteria ['showRegionD']) {
				$sql .= ', pt.region_d_name ';
			}
			if ($criteria ['showRegionE']) {
				$sql .= ', pt.region_e_name ';
			}
			if ($criteria ['showRegionF']) {
				$sql .= ', pt.region_f_name ';
			}
			if ($criteria ['showRegionG']) {
				$sql .= ', pt.region_g_name ';
			}
			if ($criteria ['showRegionH']) {
				$sql .= ', pt.region_h_name ';
			}
			if ($criteria ['showRegionI']) {
				$sql .= ', pt.region_i_name ';
			}
			if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
				$sql .= ', tpep.pepfar_category_phrase ';
			}

			if ($criteria ['showTopic']) {
				$sql .= ', ttopic.training_topic_phrase ';
			}

			if ($criteria ['showFacility']) {
				$sql .= ', pt.facility_name ';
			}

			if ($criteria ['showTot']) {
				$sql .= ", IF(pt.is_tot,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_tot";
			}

			if ($criteria ['showOrganizer']) {
				$sql .= ', torg.training_organizer_phrase ';
			}
			
			//TA:#514
			if ($criteria ['showMethod']) {
			    $sql .= ', tmet.training_method_phrase ';
			}

			if ($criteria ['showFunding']) {
				if ($criteria ['doCount']) {
					$sql .= ', tfund.funding_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT tfund.funding_phrase ORDER BY funding_phrase) as "funding_phrase" ';
				}
			}

			if ($criteria ['showQualification']) {
				$sql .= ', pq.qualification_phrase ';
			}

			if ($criteria ['showQualPrim']) {
				$sql .= ', pq.qualification_phrase ';
			}
			if ($criteria ['showQualSecond']) {
				$sql .= ', pqs.qualification_phrase AS qualification_secondary_phrase';
			}

			if ($criteria ['showRespPrim']) {
				$sql .= ', pr.responsibility_phrase as primaryResponsibility';
			}
			if ($criteria ['showRespSecond']) {
				$sql .= ', sr.responsibility_phrase  as secondaryResponsibility';
			}


			if ($criteria ['showHighestEd']) {
				$sql .= ', ed.education_level_phrase ';
			}
			if ($criteria ['showReason']) {
				$sql .= ', CASE WHEN attend_reason_other IS NOT NULL THEN attend_reason_other ELSE attend_reason_phrase END AS attend_reason_phrase ';
			}



			if ( $criteria['showCustom1'] ) {
				$sql .= ', pqc.custom1_phrase ';
			}
			if ( $criteria['showCustom2'] ) {
				$sql .= ', pqc2.custom2_phrase ';
			}
			if ( $criteria['showCustom3'] ) {
				$sql .= ', person_custom_3 ';
			}
			if ( $criteria['showCustom4'] ) {
				$sql .= ', person_custom_4 ';
			}
			if ( $criteria['showCustom5'] ) {
				$sql .= ', person_custom_5 ';
			}

			if ( $criteria['showViewingLoc']) {
				$sql .= ', person_to_training_viewing_loc_option.location_phrase '; // wont work on is_trainers report
			}
			
			if ($this->view->isScoreReport) {
				$sql .= ', spre.score_value AS score_pre, spost.score_value AS score_post';
				if ($criteria ['show_score_increase']) { //TA:#313
				    $sql .= ', ROUND((spost.score_value - spre.score_value) / spre.score_value * 100) AS score_percent_change';
				}
				if ($criteria ['show_other_scores']) {//TA:#313
				    $sql .= ', scoreother.labels, scoreother.scores ';
				}
				if($this->setting('display_training_pt_pass') !== '0'){
				 $sql .= ',spost.pass_fail as pass_fail ';//TA:#271
				}
			}

			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);

			$intersection_table = 'person_to_training';
			$intersection_person_id = 'person_id';

			if ( $this->view->is_trainers ) {
				$intersection_table = 'training_to_trainer';
				$intersection_person_id = 'trainer_id';
			}

			$sql .= ' FROM (';
			//TA:42 add person.is_delete column result
			//$sql .= 'SELECT training.*, person.facility_id as "facility_id", person.id as "person_id", person.last_name, IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, ';
			$sql .= 'SELECT training.*, person.facility_id as "facility_id", person.id as "person_id", person.is_deleted as person_is_deleted, person.last_name, IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, ';
			
			$sql .= 'person.first_name, person.middle_name, person.person_custom_1_option_id, person.person_custom_2_option_id, person.custom_3 as person_custom_3, person.custom_4 as person_custom_4, person.custom_5 as person_custom_5, ';
			$sql .= 'CASE WHEN birthdate  IS NULL OR birthdate = \'0000-00-00\' THEN NULL ELSE ((date_format(now(),\'%Y\') - date_format(birthdate,\'%Y\')) - (date_format(now(),\'00-%m-%d\') < date_format(birthdate,\'00-%m-%d\')) ) END as "age", ';
			$sql .= 'person.phone_work, person.phone_home, person.phone_mobile, person.email, ';
			$sql .= 'CASE WHEN person.active = \'deceased\' THEN \'inactive\' ELSE person.active END as "active", ';
			$sql .= 'CASE WHEN person.gender IS NULL THEN \'na\' WHEN person.gender = \'\' THEN \'na\' ELSE person.gender END as "gender", ';
			$sql .= 'primary_qualification_option_id, primary_responsibility_option_id, secondary_responsibility_option_id, highest_edu_level_option_id, attend_reason_option_id, attend_reason_other, tto.training_title_phrase AS training_title,facility.facility_name, ';
			$sql .= $intersection_table.'.id AS ptt_id, l.'.implode(', l.',$field_name);
			//TA:#529
			if ($criteria ['showPeopleFund'] || $criteria ['people_funding_id'][0]) {
			    $sql .= ' , people_funding_option.funding_phrase,  people_to_people_funding_option.funding_amount, people_to_people_funding_option.people_funding_option_id ';
			}
			$sql .= ' FROM training LEFT JOIN training_title_option tto ON training.training_title_option_id = tto.id ';
			$sql .= '    INNER JOIN '.$intersection_table.' ON training.id = '.$intersection_table.'.training_id ';
			$sql .= '    INNER JOIN person ON person.id = '.$intersection_table.'.'.$intersection_person_id;
			//TA:#529
			if ($criteria ['showPeopleFund'] || $criteria ['people_funding_id'][0]) {
			    $sql .= ' LEFT JOIN people_to_people_funding_option ON people_to_people_funding_option.person_id=person.id
    LEFT JOIN people_funding_option ON people_funding_option.id=people_to_people_funding_option.people_funding_option_id ';
			}
			$sql .= '    INNER JOIN facility ON person.facility_id = facility.id ';
			$sql .= '    LEFT JOIN ('.$location_sub_query.') AS l ON facility.location_id = l.id ';
			$sql .= '    LEFT  JOIN person_suffix_option suffix ON person.suffix_option_id = suffix.id ';
			if($criteria ['province_id'] && $criteria ['province_id'][0] !== ''){  //TA:74 filtering by province name is fixed
			     $sql .= ' where l.province_id IN ( ' . implode(",", $criteria ['province_id']) . ") ";
			}
			$sql .= ' ) as pt ';

			if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
				$sql .= '	JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';
			}

			if ($criteria ['showTopic'] || $criteria ['training_topic_id']) {
				$sql .= ' LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';
			}
			/*
			if ( $criteria['showQualification'] ) {
			$sql .= '	JOIN person_qualification_option as pq ON pq.id = pt.primary_qualification_option_id ';
			}
			*/

			if ($criteria ['showOrganizer']) {
				$sql .= '	JOIN training_organizer_option as torg ON torg.id = pt.training_organizer_option_id ';
			}
			
			//TA:#514
			if ($criteria ['showMethod']) {
			    $sql .= '	JOIN training_method_option as tmet ON tmet.id = pt.training_method_option_id ';
			}

			if ($criteria ['showFunding']) { 
				$sql .= '	LEFT JOIN (SELECT training_id, ttfo.training_funding_option_id, funding_phrase FROM training_to_training_funding_option as ttfo JOIN training_funding_option as tfo ON ttfo.training_funding_option_id = tfo.id) as tfund ON tfund.training_id = pt.id ';
			}

			if ( $criteria['showCustom1'] ) {
				$sql .= ' LEFT JOIN person_custom_1_option as pqc ON pt.person_custom_1_option_id = pqc.id  ';
			}
			if ( $criteria['showCustom2'] ) {
				$sql .= ' LEFT JOIN person_custom_2_option as pqc2 ON pt.person_custom_2_option_id = pqc2.id  ';
			}

			if ($criteria ['showQualPrim'] || $criteria ['showQualSecond'] || $criteria ['qualification_id']  || $criteria ['qualification_secondary_id']) {
				// primary qualifications
				$sql .= '
				LEFT JOIN person_qualification_option as pq ON (
				(pt.primary_qualification_option_id = pq.id AND pq.parent_id IS NULL)
				OR
				pq.id = (SELECT parent_id FROM person_qualification_option WHERE id = pt.primary_qualification_option_id LIMIT 1)
				)';

				// secondary qualifications
				$sql .= '
				LEFT JOIN person_qualification_option as pqs ON (
					pt.primary_qualification_option_id = pqs.id AND pqs.parent_id IS NOT NULL)';
			}

			if ( $criteria['showRespPrim'] ) {
				$sql .= ' LEFT JOIN person_primary_responsibility_option as pr ON pt.primary_responsibility_option_id = pr.id  ';
			}
			if ( $criteria['showRespSecond'] ) {
				$sql .= ' LEFT JOIN person_secondary_responsibility_option as sr ON pt.secondary_responsibility_option_id = sr.id  ';
			}

			if ( $criteria['showHighestEd'] ) {
				$sql .= ' LEFT JOIN person_education_level_option as ed ON pt.highest_edu_level_option_id = ed.id  ';
			}

			if ( $criteria['showReason'] ) {
				$sql .= ' LEFT JOIN person_attend_reason_option as ra ON pt.attend_reason_option_id = ra.id  ';
			}

			if ( $criteria['showViewingLoc'] ) {
				$sql .= ' LEFT JOIN (SELECT id as pttid, viewing_location_option_id,training_id FROM person_to_training) viewloc ON viewloc.pttid = ptt_id AND pt.id = viewloc.training_id';
				$sql .= ' LEFT JOIN person_to_training_viewing_loc_option ON viewing_location_option_id = person_to_training_viewing_loc_option.id ';
			}

			if ($this->view->isScoreReport) {
				$sql .= "LEFT JOIN score AS spre ON (spre.person_to_training_id = pt.ptt_id AND spre.score_label = 'Pre-Test' AND spre.is_deleted = 0)
						 LEFT JOIN score AS spost ON (spost.person_to_training_id = pt.ptt_id AND spost.score_label = 'Post-Test' AND spost.is_deleted = 0)
						 LEFT JOIN (SELECT DISTINCT person_to_training_id, GROUP_CONCAT(score_label) as labels, GROUP_CONCAT(score_value) as scores FROM score WHERE (score_label !=  'Post-Test' AND score_label != 'Pre-Test' AND is_deleted = 0) GROUP BY person_to_training_id  ) as scoreother ON (pt.ptt_id = scoreother.person_to_training_id)";
			}

			$where = array ();
 
			$where [] = ' pt.is_deleted = 0 ';
			
			//TA:42 add condition for person.is_deleted condition
			$where [] = ' person_is_deleted = 0 ';

			//TA:33 this part is not working then to do it by different way
// 			if($locWhere = $this->getLocationCriteriaWhereClause($criteria,  '', 'pt')) {
// 				$where [] = $locWhere;
// 			}
			//TA:33 use this way to get where condition for locations
			if($criteria['district_id'] && !empty($criteria['district_id']) && $criteria['district_id'][0]){
				$where [] = "pt.district_id IN (" . implode(',', $criteria['district_id']) . ")";
			}

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where []= " pt.training_organizer_option_id in ($org_allowed_ids) ";
			}

			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			if ($site_orgs)
				$where []= " pt.training_organizer_option_id in ($site_orgs) ";

			if ($criteria ['age_min']) {
				$where []= ' pt.age >= '.$criteria['age_min'];
			}
			if ($criteria ['age_max']) {
				$where []= ' pt.age <= '.$criteria['age_max'];
			}

			// legacy
			if ($this->_is_not_filter_all($criteria['training_title_option_id']) && $criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0') {
				$where [] = ' pt.training_title_option_id in (' . $this->_sql_implode($criteria['training_title_option_id']) . ') ';
			}

			if ($this->_is_not_filter_all($criteria['training_title_id']) && $criteria ['training_title_id'] or $criteria ['training_title_id'] === '0') {
				$where [] = ' pt.training_title_option_id in (' . $this->_sql_implode($criteria['training_title_id']) . ') ';
			}

			if ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				$where [] = ' ttopic.training_topic_option_id = \'' . $criteria ['training_topic_id'] . '\'';
			}

			if ($criteria ['custom_1_id'] or $criteria ['custom_1_id'] === '0') {
				$where [] = ' pt.person_custom_1_option_id = \'' . $criteria ['custom_1_id'] . '\'';
			}
			if ($criteria ['custom_2_id'] or $criteria ['custom_2_id'] === '0') {
				$where [] = ' pt.person_custom_2_option_id = \'' . $criteria ['custom_2_id'] . '\'';
			}
			if (isset($this->setting['display_people_custom3']) && $this->setting['display_people_custom3']) {
				if ($criteria ['custom_3_id'] or $criteria ['custom_3_id'] === '0') {
					$where [] = ' person_custom_3 = \'' . $criteria ['custom_3_id'] . '\'';
				}
			}
			if (isset($this->setting['display_people_custom4']) && $this->setting['display_people_custom4']) {
				if ($criteria ['custom_4_id'] or $criteria ['custom_4_id'] === '0') {
					$where [] = ' person_custom_4 = \'' . $criteria ['custom_4_id'] . '\'';
				}
			}
			if (isset($this->setting['display_people_custom5']) && $this->setting['display_people_custom5']) {
				if ($criteria ['custom_5_id'] or $criteria ['custom_5_id'] === '0') {
					$where [] = ' person_custom_5 = \'' . $criteria ['custom_5_id'] . '\'';
				}
			}

			if ($criteria ['qualification_id']) {
				$where [] = ' (pq.id = ' . $criteria ['qualification_id'] . ' OR pqs.parent_id = ' . $criteria ['qualification_id'] . ') ';
			}
			if ($criteria ['qualification_secondary_id']) {
				$where [] = ' pqs.id = ' . $criteria ['qualification_secondary_id'];
			}

			if ($criteria ['primary_responsibility_option_id']) {
				$where [] = ' pt.primary_responsibility_option_id = ' . $criteria ['primary_responsibility_option_id'] . ' ';
			}
			if ($criteria ['secondary_responsibility_option_id']) {
				$where [] = ' pt.secondary_responsibility_option_id = ' . $criteria ['secondary_responsibility_option_id'] . ' ';
			}
			if ($criteria ['highest_edu_level_option_id']) {
				$where [] = ' pt.highest_edu_level_option_id = ' . $criteria ['highest_edu_level_option_id'] . ' ';
			}
			if ($criteria ['attend_reason_option_id']) {
				$where [] = ' pt.attend_reason_option_id = ' . $criteria ['attend_reason_option_id'] . ' ';
			}


			if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0') {
				$where [] = ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';
			}

			if ($criteria ['facilityInput']) {
				$where [] = ' pt.facility_id = \'' . $criteria ['facilityInput'] . '\'';
			}

			if ($criteria ['training_gender']) {
				$where [] = ' pt.gender = \'' . $criteria ['training_gender'] . '\'';
			}

			if ($criteria ['training_active']) {
				$where [] = ' pt.active = \'' . $criteria ['training_active'] . '\'';
			}

			if ($criteria ['is_tot'] or $criteria ['is_tot'] === '0') {
				$where [] = ' pt.is_tot = ' . $criteria ['is_tot'];
			}

			if ($criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0') {
				$where [] = ' pt.training_organizer_option_id = \'' . $criteria ['training_organizer_id'] . '\'';
			}

			if ($criteria ['training_organizer_option_id'][0] && is_array ( $criteria ['training_organizer_option_id'] )) {
				$where [] = ' pt.training_organizer_option_id IN (' . implode ( ',', $criteria ['training_organizer_option_id'] ) . ')';
			}
			
			//TA:#514
			if ($criteria ['training_method_option_id'][0] && is_array ( $criteria ['training_method_option_id'] )) {
			    $where [] = ' pt.training_method_option_id IN (' . implode ( ',', $criteria ['training_method_option_id'] ) . ')';
			}
			
			if ($criteria ['funding_id'] or $criteria ['funding_id'] === '0') {
				$where [] = ' tfund.training_funding_option_id = \'' . $criteria ['funding_id'] . '\'';
			}

			if (intval ( $criteria ['end-year'] ) and $criteria ['start-year']) {
				$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where [] = ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
			}

			if ($criteria ['person_to_training_viewing_loc_option_id']) {
				$where [] = ' viewloc.viewing_location_option_id = ' . $criteria['person_to_training_viewing_loc_option_id'];
			}

			if ($this->view->isScoreReport) {
				$where [] = ' (spre.score_value != "" OR spost.score_value != "" OR scoreother.labels != "")'; // require a score to be present

				if ($criteria ['score_min']) {
					$where [] = ' spost.score_value > ' . $criteria ['score_min'];
				}
			}
			
			//TA:#529
			if ($criteria ['people_funding_id'][0]) {
			    $where [] = ' pt.people_funding_option_id IN (' . $criteria ['people_funding_id'] . ')';
			}
			

			if ($where)
			$sql .= ' WHERE ' . implode ( ' AND ', $where );

			//TA:#313
			if ($this->view->isScoreReport && $criteria ['score_percent_min'] && $criteria ['show_score_increase']) {
				$sql .= ' HAVING score_percent_change > ' . $criteria ['score_percent_min'];
			}

			if ($criteria ['doCount']) {

				$groupBy = array();

				if ( $criteria['showAge']) $groupBy []= ' pt.age ';

				if ($criteria ['showTrainingTitle']) {
					$groupBy []= ' pt.training_title_option_id';
				}
				if ($criteria ['showGender']) {
					$groupBy []= ' pt.gender';
				}
				if ($criteria ['showActive']) {
					$groupBy []= ' pt.active';
				}
				if ($criteria ['showProvince']) {
					$groupBy []= ' pt.province_id';
				}
				if ($criteria ['showDistrict']) {
					$groupBy []= ' pt.district_id';
				}
				if ($criteria ['showRegionC']) {
					$groupBy []= ' pt.region_c_id';
				}

				if ( $criteria['showRespPrim'] ) {
					$groupBy []= ' pt.primary_responsibility_option_id';
				}
				if ( $criteria['showRespSecond'] ) {
					$groupBy []= ' pt.secondary_responsibility_option_id';
				}

				if ($criteria ['showCustom1']) {
					$groupBy []= '  pt.person_custom_1_option_id';
				}
				if ($criteria ['showCustom2']) {
					$groupBy []= '  pt.person_custom_2_option_id';
				}
				if ($criteria ['showCustom3']) {
					$groupBy []= '  person_custom_3';
				}
				if ($criteria ['showCustom4']) {
					$groupBy []= '  person_custom_4';
				}
				if ($criteria ['showCustom5']) {
					$groupBy []= '  person_custom_5';
				}
				if (isset ( $criteria ['showLocation'] ) and $criteria ['showLocation']) {
					$groupBy []= '  pt.training_location_id';
				}
				if ($criteria ['showTopic']) {
					$groupBy []= '  ttopic.training_topic_option_id';
				}
				if ($criteria ['showQualification']) {
					$groupBy []= '  pt.primary_qualification_option_id';
				}
				if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') {
					$groupBy []= '  tpep.training_pepfar_categories_option_id';
				}

				if ($criteria ['showFacility']) {
					$groupBy []= '  pt.facility_id';
				}

				if ($criteria ['showTot']) {
					$groupBy []= '  pt.is_tot';
				}

				if ($criteria ['showOrganizer']) {
					$groupBy []= '  pt.training_organizer_option_id';
				}
				
				//TA:#514
				if ($criteria ['showMethod']) {
				    $groupBy []= '  pt.training_method_option_id';
				}

				if ($criteria ['showFunding']) {
					$groupBy []= '  tfund.training_funding_option_id';
				}
				if ($criteria ['showQualPrim']) {
					$groupBy []= '  pq.id ';
				}
				if ($criteria ['showQualSecond']) {
					$groupBy []= '  pqs.id ';
				}
				if ($criteria ['showViewingLoc'] || $criteria['person_to_training_viewing_loc_option_id']) {
					$groupBy []= ' viewloc.viewing_location_option_id ';
				}
				
				if ($groupBy ) {
					$groupBy = ' GROUP BY ' . implode(', ',$groupBy);
					$sql .= $groupBy;
				}
			} else {
				//TA:#529 
			    if ($criteria ['showPepfar'] || $criteria ['showTopic'] || $criteria ['showFunding'] || $criteria ['showPeopleFund'] || $criteria ['people_funding_id'][0]) {
					$sql .= ' GROUP BY person_id, pt.id';
				}
			}
			
			//print_r($criteria); print "<br><br>";
			
   //print $sql;//TA:#529
			$rowArray = $db->fetchAll ( $sql);
			

			if ($criteria ['doCount']) {
				$count = 0;
				foreach ( $rowArray as $row ) {
					$count += $row ['cnt'];
				}
			} else {
				$count = count ( $rowArray );
			}
			if ($this->getParam ( 'outputType' )){
			  $this->sendData ( $this->reportHeaders ( false, $rowArray ) );
			}

		} else {
			$count = 0;
			$rowArray = array ();
		}

		$criteria ['go'] = $this->getSanParam ( 'go' );
		
		//TA:73 show all phones
		if ($rowArray) {
		    $first = reset ( $rowArray );
// 		    if (isset ( $first ['phone_work'] )) {
		        foreach ( $rowArray as $key => $val ) {
		            $phones = array ();
		            if ($val ['phone_work'])
		                $phones [] = str_replace ( ' ', '', trim ( $val ['phone_work'] ) ) . ' (w)';
		            if ($val ['phone_home'])
		                $phones [] = str_replace ( ' ', '', trim ( $val ['phone_home'] ) ) . ' (h)';
		            if ($val ['phone_mobile'])
		                $phones [] = str_replace ( ' ', '', trim ( $val ['phone_mobile'] ) ) . ' (m)';
		            $rowArray [$key] ['phone'] = implode ( ', ', $phones );
		        }
		        $this->view->assign ( 'results', $rowArray );
//		    }
		}

		$this->viewAssignEscaped ( 'results', $rowArray );
		
//		print_r($rowArray);
		
// 		if ($rowArray) {
// 			$first = reset ( $rowArray );
// 		   if (isset ( $first ['phone_work'] )) {
// 				foreach ( $rowArray as $key => $val ) {
// 					$phones = array ();
// 					if ($val ['phone_work'])
// 					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_work'] ) ) . '&nbsp;(w)';
// 					if ($val ['phone_home'])
// 					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_home'] ) ) . '&nbsp;(h)';
// 					if ($val ['phone_mobile'])
// 					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_mobile'] ) ) . '&nbsp;(m)';
// 					$rowArray [$key] ['phone'] = implode ( ', ', $phones );
// 				}
// 				$this->view->assign ( 'results', $rowArray );
// 			}
// 		}
		
		
		$this->view->assign ( 'count', $count );
		$this->view->assign ( 'criteria', $criteria );

		//location
		$locations = Location::getAll();
		$this->viewAssignEscaped ( 'locations', $locations );
		
		//trainingTitle
		$courseArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $courseArray );
		//topics
		$topicsArray = OptionList::suggestionList ( 'training_topic_option', 'training_topic_phrase', false, false, false );
		$this->viewAssignEscaped ( 'topics', $topicsArray );
		//TA:22 funding
		$fundingsArray = OptionList::suggestionList ( 'training_funding_option', 'funding_phrase', false, false, false );
		$this->viewAssignEscaped ( 'fundings', $fundingsArray );
		//topics
		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualsArray );
		//qualifications (primary)
		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NULL' );
		$this->viewAssignEscaped ( 'qualifications_primary', $qualsArray );
		//qualifications (secondary)
		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NOT NULL' );
		$this->viewAssignEscaped ( 'qualifications_secondary', $qualsArray );
		
		//TA:#529 funding
		$fundsArray = OptionList::suggestionList ( 'people_funding_option', 'funding_phrase', false, false, false);
		$this->viewAssignEscaped ( 'people_funding', $fundsArray );

		$qualsArray = OptionList::suggestionList ( 'person_primary_responsibility_option', 'responsibility_phrase', false, false, false );
		$this->viewAssignEscaped ( 'responsibility_primary', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_secondary_responsibility_option', 'responsibility_phrase', false, false, false );
		$this->viewAssignEscaped ( 'responsibility_secondary', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_attend_reason_option', 'attend_reason_phrase', false, false, false );
		$this->viewAssignEscaped ( 'attend_reason', $qualsArray );

		$qualsArray = OptionList::suggestionList ( 'person_education_level_option', 'education_level_phrase', false, false, false);
		$this->viewAssignEscaped ( 'highest_education_level', $qualsArray );


		//pepfar
		$organizersArray = OptionList::suggestionList ( 'training_pepfar_categories_option', 'pepfar_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'pepfars', $organizersArray );
		//organizers
		//$organizersArray = OptionList::suggestionList('training_organizer_option','training_organizer_phrase',false,false,false);
		//$this->viewAssignEscaped('organizers',$organizersArray);
		//funding
		$fundingArray = OptionList::suggestionList ( 'training_funding_option', 'funding_phrase', false, false, false );
		$this->viewAssignEscaped ( 'funding', $fundingArray );

		// custom fields
		$customArray = OptionList::suggestionList ( 'person_custom_1_option', 'custom1_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom1', $customArray );
		$custom2Array = OptionList::suggestionList ( 'person_custom_2_option', 'custom2_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom2', $custom2Array );
		$custom3Array = OptionList::suggestionList ( 'person', 'custom_3', false, false, false , "custom_3 != ''");
		$this->viewAssignEscaped ( 'custom3', $custom3Array );
		$custom4Array = OptionList::suggestionList ( 'person', 'custom_4', false, false, false , "custom_4 != ''");
		$this->viewAssignEscaped ( 'custom4', $custom4Array );
		$custom5Array = OptionList::suggestionList ( 'person', 'custom_5', false, false, false , "custom_5 != ''");
		$this->viewAssignEscaped ( 'custom5', $custom5Array );
		
		//viewing location
		$viewingLocArray = OptionList::suggestionList ( 'person_to_training_viewing_loc_option', 'location_phrase', false, false, false );
		$this->viewAssignEscaped ( 'viewing_loc', $viewingLocArray );

		//organizers
		// restricted access?? only show trainings we have the ACL to view
		require_once('views/helpers/TrainingViewHelper.php');
		$org_allowed_ids = allowed_organizer_access($this);
		$orgWhere = '';
		if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
			$org_allowed_ids = implode(',', $org_allowed_ids);
			$orgWhere = " id in ($org_allowed_ids) ";
		}
		// restricted access?? only show organizers that belong to this site if its a multi org site
		$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
		if ($site_orgs) {
			$orgWhere .= $orgWhere ? " AND id in ($site_orgs) " : " id in ($site_orgs) ";
		}

		$this->view->assign ( 'organizers_checkboxes', Checkboxes::generateHtml ( 'training_organizer_option', 'training_organizer_phrase', $this->view, array(), $orgWhere ) );
		
		  $this->view->assign ( 'organizers_dropdown', DropDown::generateHtml ('training_organizer_option', 'training_organizer_phrase',
		    $criteria['training_organizer_option_id'], true, $this->view->viewonly, false,null,null,null,     true, 10 ) );
		  
		  //TA:#514
		  $this->view->assign ( 'method_dropdown', DropDown::generateHtml ('training_method_option', 'training_method_phrase',
		      $criteria['training_method_option_id'], true, $this->view->viewonly, false,null,null,null,     true, 10 ) );
		
		//facilities list
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );

	}
	

	/*
	 * TA:17:10: 10/21/2014
	 */
	public function commodityReport() {
		
		if($this->getSanParam ( 'go' )){
			$criteria = array ();
 			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
 			$facility_id = $this->getSanParam('id');
 			$sql = "SELECT commodity.id, commodity_name_option.commodity_name as commodity_name, DATE_FORMAT(commodity.date, '%m/%y') as date,
			commodity.consumption, commodity.stock_out from commodity 
			INNER JOIN commodity_name_option
				ON commodity_name_option.id=commodity.name_id where commodity.facility_id=" . $facility_id;
 			$commodity_name_option_id = $this->getSanParam('commodity_name_option_id');
 			if($commodity_name_option_id){
 				$sql = $sql . " and name_id =" . $commodity_name_option_id;
 			}
 			$dateYYstart = $this->getSanParam ( 'dateYYstart' );
 			$dateMMstart = $this->getSanParam ( 'dateMMstart' );
 			if($dateYYstart){
 				if(!$dateMMstart)
 					$dateMMstart = "01";
 				$sql = $sql . " and date > '" . $dateYYstart . "-" . $dateMMstart . "-01'";
 			}
 			$dateYYend = $this->getSanParam ( 'dateYYend' );
 			$dateMMend = $this->getSanParam ( 'dateMMend' );
 			if($dateYYend){
 				if(!$dateMMend)
 					$dateMMend = "01";
 				$sql = $sql . " and date < '" . $dateYYend . "-" . $dateMMend . "-01'";
 			}
 			
            $rowArray = $db->fetchAll ( $sql );
      
            $criteria ['go'] = $this->getSanParam ( 'go' );
            $this->view->assign ( 'count', count ( $rowArray ) );
            $criteria['commodity_name_option_id'] = $this->getSanParam('commodity_name_option_id');
            $this->view->assign ( 'criteria', $criteria );
          
            $sql = "SELECT facility_name, location_id from facility where id=" .$facility_id; 
            $facility_name = $db->fetchAll ( $sql );
			$updatedRegions = Location::getCityandParentNames($facility_name[0]['location_id'], Location::getAll(), $this->setting('num_location_tiers'));
            
			for($i=0; $i<count($rowArray); $i++){
				$rowArray[$i]['province_name'] = $updatedRegions['province_name'];
				$rowArray[$i]['district_name'] = $updatedRegions['district_name'];
				$rowArray[$i]['facility_name'] = $facility_name[0]['facility_name'];
            }
            $this->viewAssignEscaped ( 'results', $rowArray );
          
            $this->view->assign ( 'commodity_name_id', $commodity_name_option_id);
            $this->view->assign ( 'dateMMstart', $dateMMstart);
            $this->view->assign ( 'dateYYstart', $dateYYstart);
            $this->view->assign ( 'dateMMend', $dateMMend);
            $this->view->assign ( 'dateYYend', $dateYYend);
            
            if ($this->getParam ( 'outputType' )){
            	$this->sendData ( $this->reportHeaders ( false, $rowArray ) );
            }
            
		}
		
		$commodity_names = OptionList::suggestionList ( 'commodity_name_option', 'commodity_name', false, false, false );
		$this->viewAssignEscaped ( 'commodity_names', $commodity_names );
		$this->viewAssignEscaped ( 'facility_id', $facility_id );
	}

	public function participantsByTrainingAction() {
		$this->view->assign ( 'mode', 'count' );
		return $this->peopleReport ();
	}
	
	/*
	 * TA:17:10: 10/21/2014
	 */
	public function commodityByFacilityAction() {
		$this->view->assign ( 'mode', 'count' );
		return $this->commodityReport ();
	}

	public function participantsScoresAction() {
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'isScoreReport', TRUE );
		return $this->peopleReport ();
	}

	public function participantsByNameAction() {
		$this->view->assign ( 'mode', 'id' );
		return $this->peopleReport ();
	}

	public function participantsByFacilityTypeAction() {
		require_once ('views/helpers/DropDown.php');
		$this->view->assign ( 'mode', 'id' );
		$criteria = array ();

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = '0000-00-00';
		if ($rowArray and $rowArray [0] ['start'])
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = $parts [0];
		$criteria ['start-month'] = $parts [1];
		$criteria ['start-day'] = $parts [2];

		$criteria ['showAge'] = $this->getSanParam ( 'showAge' );
		$criteria ['age_min'] = $this->getSanParam ( 'age_min' );
		$criteria ['age_max'] = $this->getSanParam ( 'age_max' );

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		$criteria ['end-year'] = date ( 'Y' );
		$criteria ['end-month'] = date ( 'm' );
		$criteria ['end-day'] = date ( 'd' );
		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );
		
		//TA:38 fixing bug to filter by geography
		$criteria ['province_id'] = $this->getSanParam ( 'province_id' );
		$arr_dist = $this->getSanParam ( 'district_id' );
		//print "+" . $criteria ['province_id'] . "+"; // this is array of ids
		// level 2 location has parameter as [parent_location_id]_[location_id], we need to take only location_ids
		for($i=0;$i<sizeof($arr_dist); $i++){
			if ( strstr($arr_dist[$i], '_') !== false ) {
				$parts = explode('_',$arr_dist[$i]);
				$arr_dist[$i] = $parts[1];
			}
		}
		$criteria ['district_id'] = $arr_dist;
		///

		//TA:38 list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);

		$criteria ['training_gender'] = $this->getSanParam ( 'training_gender' );
		$criteria ['training_active'] = $this->getSanParam ( 'training_active' );
		$criteria ['concatNames'] = $this->getSanParam ( 'concatNames' );
		$criteria ['training_title_option_id'] = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['training_title_id'] = $this->getSanParam ( 'training_title_id' );
		$criteria ['training_pepfar_id'] = $this->getSanParam ( 'training_pepfar_id' );
		$criteria ['training_topic_id'] = $this->getSanParam ( 'training_topic_id' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['facility_type_id'] = $this->getSanParam ( 'facility_type_id' );
		$criteria ['facility_sponsor_id'] = $this->getSanParam ( 'facility_sponsor_id' );
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['is_tot'] = $this->getSanParam ( 'is_tot' );
		$criteria ['funding_id'] = $this->getSanParam ( 'funding_id' );
		$criteria ['training_organizer_id'] = $this->getSanParam ( 'training_organizer_id' );
		$criteria ['training_organizer_option_id'] = $this->getSanParam ( 'training_organizer_option_id' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['qualification_secondary_id'] = $this->getSanParam ( 'qualification_secondary_id' );
		$criteria ['qualification_option_id'] =       $this->getSanParam ( 'qualification_option_id' );

		$criteria ['custom_1_id']                  = $this->getSanParam ( 'custom_1_id' );
		$criteria ['custom_2_id']                  = $this->getSanParam ( 'custom_2_id' );
		$criteria ['custom_3_id']                  = $this->getSanParam ( 'custom_3_id' );
		$criteria ['custom_4_id']                  = $this->getSanParam ( 'custom_4_id' );
		$criteria ['custom_5_id']                  = $this->getSanParam ( 'custom_5_id' );
		
		$criteria ['doCount'] = ($this->view->mode == 'count');
		$criteria ['showProvince'] = ($this->getSanParam ( 'showProvince' ) or ($criteria ['doCount'] and ($criteria ['province_id'] or $criteria ['province_id'] === '0')));
		$criteria ['showDistrict'] = ($this->getSanParam ( 'showDistrict' ) or ($criteria ['doCount'] and ($criteria ['district_id'] or $criteria ['district_id'] === '0')));
		$criteria ['showRegionC'] = ($this->getSanParam ( 'showRegionC' ) or ($criteria ['doCount'] and ($criteria ['region_c_id'] or ! empty ( $criteria ['region_c_id'] ))));
		$criteria ['showTrainingTitle'] = ($this->getSanParam ( 'showTrainingTitle' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_title_id'] or $criteria ['training_title_option_id'] === '0')));
		$criteria ['showPepfar'] = ($this->getSanParam ( 'showPepfar' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_title_id'] or $criteria ['training_pepfar_id'] === '0')));
		$criteria ['showTopic'] = ($this->getSanParam ( 'showTopic' ) or ($criteria ['doCount'] and ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')));
		$criteria ['showFacility'] = ($this->getSanParam ( 'showFacility' ) or ($criteria ['doCount'] and $criteria ['facilityInput']));
		$criteria ['showGender'] = ($this->getSanParam ( 'showGender' ) or ($criteria ['doCount'] and $criteria ['training_gender']));
		$criteria ['showActive'] = ($this->getSanParam ( 'showActive' ) or ($criteria ['doCount'] and $criteria ['training_active']));
		$criteria ['showEmail'] = ($this->getSanParam ( 'showEmail' ));
		$criteria ['showPhone'] = ($this->getSanParam ( 'showPhone' ));
		$criteria ['showType'] = ($this->getSanParam ( 'showType' ) or ($criteria ['doCount'] and ($criteria ['facility_type_id'] or $criteria ['facility_type_id'] === '0')));
		$criteria ['showSponsor'] = ($this->getSanParam ( 'showSponsor' ) or ($criteria ['doCount'] and $criteria ['facility_sponsor_id']));
		$criteria ['showTot'] = ($this->getSanParam ( 'showTot' ) or ($criteria ['doCount'] and $criteria ['is_tot'] !== '' or $criteria ['is_tot'] === '0'));
		$criteria ['showFunding'] = ($this->getSanParam ( 'showFunding' ) or ($criteria ['doCount'] and $criteria ['funding_id'] or $criteria ['funding_id'] === '0'));
		$criteria ['showOrganizer'] = ($this->getSanParam ( 'showOrganizer' ) or ($criteria ['doCount'] and ($criteria ['training_organizer_option_id'])));
		$criteria ['showQualPrim'] = ($this->getSanParam ( 'showQualPrim' ) or ($criteria ['doCount'] and ($criteria ['qualification_id'] or $criteria ['qualification_id'] === '0')));
		$criteria ['showQualSecond'] = ($this->getSanParam ( 'showQualSecond' ) or ($criteria ['doCount'] and ($criteria ['qualification_secondary_id'] or $criteria ['qualification_secondary_id'] === '0')));
		$criteria ['showQualification'] = ($this->getSanParam ( 'showQualification' ) or ($criteria ['doCount'] and ($criteria ['qualification_option_id'] or $criteria ['qualification_option_id'] === '0')));

		$criteria ['showCustom1']       = ($this->getSanParam ( 'showCustom1' ));
		$criteria ['showCustom2']       = ($this->getSanParam ( 'showCustom2' ));
		$criteria ['showCustom3']       = ($this->getSanParam ( 'showCustom3' ));
		$criteria ['showCustom4']       = ($this->getSanParam ( 'showCustom4' ));
		$criteria ['showCustom5']       = ($this->getSanParam ( 'showCustom5' ));
		

		$criteria ['go'] = $this->getSanParam ( 'go' );
		if ($criteria ['go']) {

			$sql = 'SELECT ';

			if ($criteria ['doCount']) {
				$sql .= ' COUNT(person_id) as "cnt" ';
			} else {
				if ($criteria ['concatNames'])
				$sql .= ' DISTINCT person_id as "id", CONCAT(first_name, ' . "' '" . ',last_name, ' . "' '" . ', IFNULL(suffix_phrase, ' . "' '" . ')) as "name" ';
				else
				$sql .= ' DISTINCT person_id as "id", IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, last_name, first_name, middle_name  ';
			}
			if ($criteria ['showPhone']) {
				$sql .= ", CASE WHEN (pt.phone_work IS NULL OR pt.phone_work = '') THEN NULL ELSE pt.phone_work END as \"phone_work\", CASE WHEN (pt.phone_home IS NULL OR pt.phone_home = '') THEN NULL ELSE pt.phone_home END as \"phone_home\", CASE WHEN (pt.phone_mobile IS NULL OR pt.phone_mobile = '') THEN NULL ELSE pt.phone_mobile END as \"phone_mobile\" ";
			}
			if ($criteria ['showEmail']) {
				$sql .= ', pt.email ';
			}
			if ($criteria ['showGender']) {
				$sql .= ', pt.gender ';
			}
			if ($criteria ['showAge']) {
				$sql .= ', pt.age ';
			}
			if ($criteria ['showActive']) {
				$sql .= ', pt.active ';
			}
			if ($criteria ['showTrainingTitle']) {
				$sql .= ', pt.training_title, pt.training_start_date ';
			}
			if ($criteria ['showRegionC']) {
				$sql .= ', pt.region_c_name ';
			}
			if ($criteria ['showDistrict']) {
				$sql .= ', pt.district_name ';
			}
			if ($criteria ['showProvince']) {
				$sql .= ', pt.province_name ';
			}


			if ($criteria ['showPepfar']) {
				$sql .= ', tpep.pepfar_category_phrase ';
			}

			if ($criteria ['showOrganizer']) {
				$sql .= ', torg.training_organizer_phrase ';
			}

			if ($criteria ['showFunding']) {
				$sql .= ', tfund.funding_phrase ';
			}

			if ($criteria ['showTopic']) {
				$sql .= ', ttopic.training_topic_phrase ';
			}

			if ($criteria ['showType']) {
				$sql .= ', fto.facility_type_phrase ';
			}

			if ($criteria ['showSponsor']) {
				$sql .= ', fso.facility_sponsor_phrase ';
			}
			if ($criteria ['showFacility']) {
				$sql .= ', pt.facility_name ';
			}

			if ($criteria ['showTot']) {
				//$sql .= ', pt.is_tot ';
				$sql .= ", IF(pt.is_tot,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_tot";
			}

			if ($criteria ['showQualPrim']) {
				$sql .= ', pq.qualification_phrase ';
			}
			if ($criteria ['showQualSecond']) {
				$sql .= ', pqs.qualification_phrase AS qualification_secondary_phrase';
			}
			if ($criteria ['showQualification']) {
				$sql .= ', pq.qualification_phrase';
			}

			if ( $criteria['showCustom1'] ) {
				$sql .= ', pqc.custom1_phrase ';
			}
			if ( $criteria['showCustom2'] ) {
				$sql .= ', pqc2.custom2_phrase ';
			}
			if ( $criteria['showCustom3'] ) {
				$sql .= ', person_custom_3 ';
			}
			if ( $criteria['showCustom4'] ) {
				$sql .= ', person_custom_4 ';
			}
			if ( $criteria['showCustom5'] ) {
				$sql .= ', person_custom_5 ';
			}

			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);

			$sql .= ' FROM ( SELECT training.*, person.facility_id as "facility_id", person.id as "person_id", person.last_name, IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, ';
			$sql .= '      person.first_name, person.middle_name, person.person_custom_1_option_id, person.person_custom_2_option_id, person.custom_3 as person_custom_3, person.custom_4 as person_custom_4, person.custom_5 as person_custom_5, person.phone_work, person.phone_home, person.phone_mobile, person.email, ';
			$sql .= '      CASE WHEN birthdate  IS NULL OR birthdate = \'0000-00-00\' THEN NULL ELSE ((date_format(now(),\'%Y\') - date_format(birthdate,\'%Y\')) - (date_format(now(),\'00-%m-%d\') < date_format(birthdate,\'00-%m-%d\')) ) END as "age", ';
			$sql .= '      CASE WHEN person.active = \'deceased\' THEN \'inactive\' ELSE person.active END as "active", ';
			$sql .= '      CASE WHEN person.gender IS NULL THEN \'na\' WHEN person.gender = \'\' THEN \'na\' ELSE person.gender END as "gender", ';
			$sql .= '      primary_qualification_option_id, tto.training_title_phrase AS training_title,facility.facility_name, facility.type_option_id, facility.sponsor_option_id, ';
			$sql .= '      l.'.implode(', l.',$field_name);
			$sql .= ' FROM training INNER JOIN training_title_option tto ON training.training_title_option_id = tto.id ';
			$sql .= ' INNER JOIN person_to_training ON training.id = person_to_training.training_id ';
			$sql .= ' INNER JOIN person ON person.id = person_to_training.person_id ';
			$sql .= ' INNER JOIN facility ON person.facility_id = facility.id ';
			$sql .= ' LEFT JOIN ('.$location_sub_query.') as l ON facility.location_id = l.id';
			$sql .= ' LEFT  JOIN person_suffix_option suffix ON person.suffix_option_id = suffix.id ';
			$sql .= ' ) as pt ';


			if ($criteria ['showPepfar']) {
				$sql .= '	JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';
			}

			if ($criteria ['showTopic']) {
				$sql .= '	LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';
				//$sql .= '	LEFT JOIN training_topic_option as ttopic ON ttopic.id = ttopic.training_topic_option_id ';
			}

			if ($criteria ['showType']) {
				$sql .= '	JOIN facility_type_option as fto ON fto.id = pt.type_option_id ';
			}

			if ($criteria ['showSponsor']) {
				$sql .= '	JOIN facility_sponsor_option as fso ON fso.id = pt.sponsor_option_id ';
			}

			if ($criteria ['showOrganizer']) {
				$sql .= '	JOIN training_organizer_option as torg ON torg.id = pt.training_organizer_option_id ';
			}

			if ($criteria ['showFunding']) {
				$sql .= '	LEFT JOIN (SELECT training_id, ttfo.training_funding_option_id, funding_phrase FROM training_to_training_funding_option as ttfo JOIN training_funding_option as tfo ON ttfo.training_funding_option_id = tfo.id) as tfund ON tfund.training_id = pt.id ';
			}

			if ( $criteria['showCustom1'] ) {
				$sql .= ' LEFT JOIN person_custom_1_option as pqc ON pt.person_custom_1_option_id = pqc.id  ';
			}
			if ( $criteria['showCustom2'] ) {
				$sql .= ' LEFT JOIN person_custom_2_option as pqc2 ON pt.person_custom_2_option_id = pqc2.id  ';
			}

			if ($criteria ['showQualPrim'] || $criteria ['showQualSecond']) {
				// primary qualifications
				$sql .= '
				LEFT JOIN person_qualification_option as pq ON (
				(pt.primary_qualification_option_id = pq.id AND pq.parent_id IS NULL)
				OR
				pq.id = (SELECT parent_id FROM person_qualification_option WHERE id = pt.primary_qualification_option_id LIMIT 1)
				)';

				// secondary qualifications
				$sql .= '
				LEFT JOIN person_qualification_option as pqs ON (
				pt.primary_qualification_option_id = pqs.id AND pqs.parent_id IS NOT NULL
				)';
			}

			if ($criteria ['showQualification']) {
				$sql .= '  LEFT JOIN person_qualification_option as pq ON pq.id = primary_qualification_option_id';
			}

			$where = array();

			$where []= 'pt.is_deleted=0 ';
			
			//TA:38 use this way to get where condition for locations
// 			if($criteria['district_id'] && !empty($criteria['district_id']) && $criteria['district_id'][0]){
// 				$where [] = "pt.district_id IN (" . $criteria['district_id'] . ")";
// 			}
			if($criteria['district_id'] && !empty($criteria['district_id']) && $criteria['district_id'][0]){
				$where [] = "pt.district_id IN (" . implode(',', $criteria['district_id']) . ")";
			}
			
			//TA:67 filter by province
			if ($criteria ['province_id'] && ! empty ( $criteria ['province_id'] && $criteria['province_id'][0])) {
			    $where [] = ' pt.province_id IN (' . implode ( ',', $criteria ['province_id'] ) . ')';
			}

			if ($criteria ['age_min']) {
				$where []= ' pt.age >= '.$criteria['age_min'];
			}
			if ($criteria ['age_max']) {
				$where []= ' pt.age <= '.$criteria['age_max'];
			}

			// not used
			if ($this->_is_not_filter_all($criteria['training_title_option_id']) && $criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0') {
				$where [] = ' pt.training_title_option_id in (' . $this->_sql_implode($criteria['training_title_option_id']) . ') ';
			}

			if ($this->_is_not_filter_all($criteria['training_title_id']) && $criteria ['training_title_id'] or $criteria ['training_title_id'] === '0') {
				$where [] = ' pt.training_title_option_id in (' . $this->_sql_implode($criteria['training_title_id']) . ') ';
			}

			if ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				$where []= ' ttopic.training_topic_option_id = \'' . $criteria ['training_topic_id'] . '\'';
			}
			/*
			if ( $criteria['qualification_id'] or $criteria['qualification_id'] === '0'  ) {
			if ( strlen($where) ) $where .= ' AND ';
			$where .= ' pt.primary_qualification_option_id = \''.$criteria['qualification_id'].'\'' ;
			}
			*/
			if ($criteria ['qualification_id']) {
				$where []= ' (pq.id = ' . $criteria ['qualification_id'] . ' OR pqs.parent_id = ' . $criteria ['qualification_id'] . ') ';
			}
			if ($criteria ['qualification_secondary_id']) {
				$where []= ' pqs.id = ' . $criteria ['qualification_secondary_id'];
			}
			if ($criteria ['qualification_option_id']) { // this is the one we use
				$where []= ' pq.id = ' . $criteria ['qualification_option_id'];
			}

			if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0') {
				$where []= ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';
			}

			if ($criteria ['facilityInput']) {
				$where []= ' pt.facility_id = \'' . $criteria ['facilityInput'] . '\'';
			}

			if ($criteria ['facility_type_id'] or $criteria ['facility_type_id'] === '0') {
				$where []= ' pt.type_option_id = \'' . $criteria ['facility_type_id'] . '\'';
			}
			if ($criteria ['facility_sponsor_id'] or $criteria ['facility_sponsor_id'] === '0') {
				$where []= ' pt.sponsor_option_id = \'' . $criteria ['facility_sponsor_id'] . '\'';
			}

			if ($criteria ['training_gender']) {
				$where []= ' pt.gender = \'' . $criteria ['training_gender'] . '\'';
			}

			if ($criteria ['training_active']) {
				$where []= ' pt.active = \'' . $criteria ['training_active'] . '\'';
			}

			if ($criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0') {
				$where []= ' pt.training_organizer_option_id = \'' . $criteria ['training_organizer_id'] . '\'';
			}

			if ($criteria ['training_organizer_option_id'] && is_array ( $criteria ['training_organizer_option_id'] )) {
				$where []= ' pt.training_organizer_option_id IN (' . implode ( ',', $criteria ['training_organizer_option_id'] ) . ')';
			}

			if ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				$where []= ' ttopic.training_topic_option_id = \'' . $criteria ['training_topic_id'] . '\'';
			}

			if ($criteria ['is_tot'] or $criteria ['is_tot'] === '0') {
				$where []= ' pt.is_tot = ' . $criteria ['is_tot'];
			}

			if ($criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0') {
				$where []= ' pt.training_organizer_option_id = \'' . $criteria ['training_organizer_id'] . '\'';
			}

			if ($criteria ['funding_id'] or $criteria ['funding_id'] === '0') {
				$where []= ' tfund.training_funding_option_id = \'' . $criteria ['funding_id'] . '\'';
			}

			if (intval ( $criteria ['end-year'] ) and $criteria ['start-year']) {
				$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where []= ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
			}

			if ($criteria ['custom_1_id'] or $criteria ['custom_1_id'] === '0') {
				$where [] = ' pt.person_custom_1_option_id = \'' . $criteria ['custom_1_id'] . '\'';
			}
			if ($criteria ['custom_2_id'] or $criteria ['custom_2_id'] === '0') {
				$where [] = ' pt.person_custom_2_option_id = \'' . $criteria ['custom_2_id'] . '\'';
			}
			if (isset($this->setting['display_people_custom3']) && $this->setting['display_people_custom3']) {
				if ($criteria ['custom_3_id'] or $criteria ['custom_3_id'] === '0') {
					$where [] = ' person_custom_3 = \'' . $criteria ['custom_3_id'] . '\'';
				}
			}
			if (isset($this->setting['display_people_custom4']) && $this->setting['display_people_custom4']) {
				if ($criteria ['custom_4_id'] or $criteria ['custom_4_id'] === '0') {
					$where [] = ' person_custom_4 = \'' . $criteria ['custom_4_id'] . '\'';
				}
			}
			if (isset($this->setting['display_people_custom5']) && $this->setting['display_people_custom5']) {
				if ($criteria ['custom_5_id'] or $criteria ['custom_5_id'] === '0') {
					$where [] = ' person_custom_5 = \'' . $criteria ['custom_5_id'] . '\'';
				}
			}

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where []= " pt.training_organizer_option_id in ($org_allowed_ids) ";
			}
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			if ($site_orgs)
				$where []= " pt.training_organizer_option_id in ($site_orgs) ";

			if ($where)
			$sql .= ' WHERE ' . implode(' AND ',$where);
			
			$rowArray = $db->fetchAll ( $sql );

			if ($criteria ['doCount']) {
				$count = 0;
				foreach ( $rowArray as $row ) {
					$count += $row ['cnt'];
				}
			} else {
				$count = count ( $rowArray );
			}
			if ($this->getParam ( 'outputType' ))
			$this->sendData ( $this->reportHeaders ( false, $rowArray ) );

		} else {
			$count = 0;
			$rowArray = array ();
		}

		$criteria ['go'] = $this->getSanParam ( 'go' );

		$this->viewAssignEscaped ( 'results', $rowArray );
		if ($rowArray) {
			$first = reset ( $rowArray );
			if (isset ( $first ['phone_work'] )) {
				foreach ( $rowArray as $key => $val ) {
					$phones = array ();
					if ($val ['phone_work'])
					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_work'] ) ) . '&nbsp;(w)';
					if ($val ['phone_home'])
					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_home'] ) ) . '&nbsp;(h)';
					if ($val ['phone_mobile'])
					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_mobile'] ) ) . '&nbsp;(m)';
					$rowArray [$key] ['phone'] = implode ( ', ', $phones );
				}
				$this->view->assign ( 'results', $rowArray );
			}
		}

		$this->view->assign ( 'count', $count );
		$this->view->assign ( 'criteria', $criteria );

		//locations
		$locations = Location::getAll();
		$this->viewAssignEscaped('locations',$locations);
		//courses
		$courseArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $courseArray );
		//topics
		$topicsArray = OptionList::suggestionList ( 'training_topic_option', 'training_topic_phrase', false, false, false );
		$this->viewAssignEscaped ( 'topics', $topicsArray );
		//qualifications (primary)
		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NULL' );
		$this->viewAssignEscaped ( 'qualifications_primary', $qualsArray );
		//qualifications (secondary)
		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NOT NULL' );
		$this->viewAssignEscaped ( 'qualifications_secondary', $qualsArray );

		//pepfar
		$organizersArray = OptionList::suggestionList ( 'training_pepfar_categories_option', 'pepfar_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'pepfars', $organizersArray );
		//facility types
		$typesArray = OptionList::suggestionList ( 'facility_type_option', 'facility_type_phrase', false, false );
		$this->viewAssignEscaped ( 'facility_types', $typesArray );
		//sponsor types
		$sponsorsArray = OptionList::suggestionList ( 'facility_sponsor_option', 'facility_sponsor_phrase', false, false );
		$this->viewAssignEscaped ( 'facility_sponsors', $sponsorsArray );
		//organizers
		$organizersArray = OptionList::suggestionList ( 'training_organizer_option', 'training_organizer_phrase', false, false, false );
		$this->viewAssignEscaped ( 'organizers', $organizersArray );
		//funding
		$fundingArray = OptionList::suggestionList ( 'training_funding_option', 'funding_phrase', false, false, false );
		$this->viewAssignEscaped ( 'funding', $fundingArray );
		
		// custom fields
		$customArray = OptionList::suggestionList ( 'person_custom_1_option', 'custom1_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom1', $customArray );
		$custom2Array = OptionList::suggestionList ( 'person_custom_2_option', 'custom2_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom2', $custom2Array );
		$custom3Array = OptionList::suggestionList ( 'person', 'custom_3', false, false, false , "custom_3 != ''");
		$this->viewAssignEscaped ( 'custom3', $custom3Array );
		$custom4Array = OptionList::suggestionList ( 'person', 'custom_4', false, false, false , "custom_4 != ''");
		$this->viewAssignEscaped ( 'custom4', $custom4Array );
		$custom5Array = OptionList::suggestionList ( 'person', 'custom_5', false, false, false , "custom_5 != ''");
		$this->viewAssignEscaped ( 'custom5', $custom5Array );
		
		//organizers
		// restricted access?? only show trainings we have the ACL to view
		require_once('views/helpers/TrainingViewHelper.php');
		$org_allowed_ids = allowed_organizer_access($this);
		$orgWhere = '';
		if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
			$org_allowed_ids = implode(',', $org_allowed_ids);
			$orgWhere = " id in ($org_allowed_ids) ";
		}
		// restricted access?? only show organizers that belong to this site if its a multi org site
		$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
		if ($site_orgs) {
			$orgWhere .= $orgWhere ? " AND id in ($site_orgs) " : " id in ($site_orgs) ";
		}
		// restricted access?? only show organizers that belong to this site if its a multi org site
		$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
		if ($site_orgs) {
			$orgWhere .= $orgWhere ? " AND id in ($site_orgs) " : " id in ($site_orgs) ";
		}

		$this->view->assign ( 'organizers_checkboxes', Checkboxes::generateHtml ( 'training_organizer_option', 'training_organizer_phrase', $this->view, array(), $orgWhere ) );
		
		$this->view->assign ( 'organizers_dropdown', DropDown::generateHtml ('training_organizer_option', 'training_organizer_phrase',
			$criteria['training_organizer_option_id'], true, $this->view->viewonly, false,null,null,null,     true, 10 ) );
		
		//facilities list
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
		//qualifactions
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false, array ('0 AS is_default', 'child.is_default' ) );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
	}

	public function trainersAction() {
		require_once ('models/table/Person.php');
		require_once ('models/table/Trainer.php');

		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);

		$criteria ['skill_id'] = $this->getSanParam ( 'trainer_skill_id' );
		if (is_array ( $criteria ['skill_id'] ) && $criteria ['skill_id'] [0] === "") { // "All"
			$criteria ['skill_id'] = array ();
		}

		$criteria ['concatNames'] = $this->getSanParam ( 'concatNames' );
		$criteria ['type_id'] = $this->getSanParam ( 'trainer_type_id' );
		$criteria ['language_id'] = $this->getSanParam ( 'trainer_language_id' );
		$criteria ['training_topic_option_id'] = $this->getSanParam ( 'training_topic_option_id' ); // checkboxes
		$criteria ['go'] = $this->getSanParam ( 'go' );

		if ($criteria ['go']) {

			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$sql = " SELECT DISTINCT " . " `person`.`id`, ";
			if ($criteria ['concatNames']) {
				$sql .= " CONCAT( `person`.`first_name`, ' ',`person`.`last_name`) as \"name\", ";
			} else {
				$sql .= " `person`.`first_name`, ";
				$sql .= " `person`.`last_name`, ";
				$sql .= " `person`.`middle_name`, ";
				$sql .= " IFNULL(`person_suffix_option`.`suffix_phrase`, ' ') as `suffix_phrase`, ";
			}

			// get training topics taught
			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);


			$sql .= " `t`.`type_option_id`, " . " `tt`.`trainer_type_phrase`, " . " `ts`.`trainer_skill_phrase`,	 `tl`.`language_phrase`, " . " topics.training_topic_phrase, l.".implode(', l.',$field_name).
			" FROM `person` " . " JOIN `trainer` AS `t` ON t.person_id = person.id " .
			" JOIN `facility` as f ON person.facility_id = f.id  JOIN (".$location_sub_query.") as l ON f.location_id = l.id " .
			" LEFT JOIN `trainer_type_option` AS `tt` ON t.type_option_id = tt.id " .
			" LEFT JOIN (SELECT trainer_id, trainer_skill_phrase, trainer_skill_option_id FROM trainer_to_trainer_skill_option JOIN trainer_skill_option ON trainer_to_trainer_skill_option.trainer_skill_option_id = trainer_skill_option.id) as ts ON t.person_id = ts.trainer_id " .
			" LEFT JOIN (SELECT trainer_id, language_phrase, trainer_language_option_id FROM trainer_to_trainer_language_option JOIN trainer_language_option ON trainer_to_trainer_language_option.trainer_language_option_id = trainer_language_option.id) as tl ON t.person_id = tl.trainer_id " .
			" LEFT JOIN `person_suffix_option` ON `person`.`suffix_option_id` = `person_suffix_option`.id ";

			$sql .= " LEFT JOIN (

			SELECT ttt.trainer_id, training_topic_phrase, training_topic_option_id
			FROM training_to_trainer ttt
			JOIN training_to_training_topic_option ttto ON ttto.training_id = ttt.training_id
			JOIN training_topic_option tto ON tto.id = ttto.training_topic_option_id
			) as topics ON t.person_id = topics.trainer_id ";

			$where = array();

			if ($criteria ['type_id'] or ($criteria ['type_id'] === '0')) {
				$where []= ' t.type_option_id = ' . $criteria ['type_id'];
			}

			if (! empty ( $criteria ['skill_id'] )) {
				$where []= ' trainer_skill_option_id IN (' . implode ( ',', $criteria ['skill_id'] ) . ')';
			}

			if ($criteria ['language_id']) {
				$where []= ' trainer_language_option_id = ' . $criteria ['language_id'];
			}

			if ($criteria ['training_topic_option_id']) {
				$where []= ' training_topic_option_id IN (' . implode ( ',', $criteria ['training_topic_option_id'] ) . ')';
			}

			if ($where)
			$sql .= ' WHERE ' . implode(' AND ',$where);

			//$sql .= ' GROUP BY person.id ';


			$sql .= " ORDER BY " . " `person`.`last_name` ASC, " . " `person`.`first_name` ASC ";

			//	echo $sql; exit;


			$rowArray = $db->fetchAll ( $sql );
			if ($this->getParam ( 'outputType' ))
			$this->sendData ( $this->reportHeaders ( false, $rowArray ) );

		} else {
			$rowArray = array ();
		}

		$this->viewAssignEscaped ( 'results', $rowArray );
		$this->view->assign ( 'count', count ( $rowArray ) );
		$this->view->assign ( 'criteria', $criteria );

		//locations
		$locations = Location::getAll();
		$this->viewAssignEscaped ( 'locations', $locations );
		//types
		$trainerTypesArray = OptionList::suggestionList ( 'trainer_type_option', 'trainer_type_phrase', false, false, false );
		$this->viewAssignEscaped ( 'types', $trainerTypesArray );
		//skillz
		$trainerSkillsArray = OptionList::suggestionList ( 'trainer_skill_option', 'trainer_skill_phrase', false, false );
		$this->viewAssignEscaped ( 'skills', $trainerSkillsArray );
		//languages
		$trainerLanguagesArray = OptionList::suggestionList ( 'trainer_language_option', 'language_phrase', false, false );
		$this->viewAssignEscaped ( 'language', $trainerLanguagesArray );
		//topics
		$this->view->assign ( 'topic_checkboxes', Checkboxes::generateHtml ( 'training_topic_option', 'training_topic_phrase', $this->view ) );

	}
	/*
	public function participantsByCategoryAction() {

	$criteria = array();
	$criteria['start-year'] = date('Y');
	$criteria['start-month'] = date('m');
	$criteria['start-day'] = '01';

	$criteria['cat'] = $this->getSanParam('cat');
	if ( $this->getSanParam('start-year') )
	$criteria['start-year'] = $this->getSanParam('start-year');
	if ( $this->getSanParam('start-month') )
	$criteria['start-month'] = $this->getSanParam('start-month');
	if ( $this->getSanParam('start-day') )
	$criteria['start-day'] = $this->getSanParam('start-day');

	//province
	$provinceArray = OptionList::suggestionList('location_province','province_name',false,false);
	$this->view->assign('provinces',$provinceArray);
	//district
	$districtArray = OptionList::suggestionList('location_district',array('district_name','parent_province_id'),false,false);
	$this->view->assign('districts',$districtArray);
	//http://localhost/itech/web/html/reports/participantsByCategory/cat/pepfar?province_id=&district_id=&start-month=&start-day=&start-year=&go=Preview

	$criteria['district_id'] = $this->getSanParam('district_id');
	$criteria['province_id'] = $this->getSanParam('province_id');

	//Q1 query UNION
	//Q2 query UNION
	//Q3 query UNION
	//Q4 query

	//make sure the date doesn't go back too far
	if ( $criteria['start-year'] < 2000 ) {
	$criteria['start-year'] = 2000;
	}

	$qDate = $criteria['start-year'].'-'.$criteria['start-month'].'-'.$criteria['start-day'];
	$results = array();
	if ( $this->getSanParam('go') ) {
	$db = Zend_Db_Table_Abstract::getDefaultAdapter();
	$grandTotal = 0;
	while( $qDate != '' AND (strtotime($qDate) < time()) ) {
	switch ( $criteria['cat'] ) {
	case 'level':
	$sql = 'SELECT count(DISTINCT ptt.id) as "cnt", tlo.training_level_phrase as "cat", \''.$qDate.'\'+ INTERVAL 3 MONTH - INTERVAL 1 DAY as "quarter_end",  \''.$qDate.'\'+ INTERVAL 3 MONTH as "next_quarter_start" FROM '.
	' person_to_training as ptt JOIN training as t ON ptt.training_id = t.id ';
	$sql .= ' LEFT JOIN training_level_option as tlo ON t.training_level_option_id = tlo.id ';
	break;
	case 'qualification':
	$sql = 'SELECT count(DISTINCT ptt.id) as "cnt", pqo.qualification_phrase as "cat", \''.$qDate.'\'+ INTERVAL 3 MONTH - INTERVAL 1 DAY as "quarter_end",  \''.$qDate.'\'+ INTERVAL 3 MONTH as "next_quarter_start" FROM '.
	' person_to_training as ptt JOIN training as t ON ptt.training_id = t.id ';
	$sql .= '  JOIN person as p ON ptt.person_id = p.id ';
	$sql .= ' LEFT JOIN person_qualification_option as pqo ON p.primary_qualification_option_id = pqo.id ';
	break;
	case 'pepfar':
	$sql = 'SELECT count(DISTINCT ptt.id) as "cnt", pfr.pepfar_category_phrase as "cat", \''.$qDate.'\'+ INTERVAL 3 MONTH - INTERVAL 1 DAY as "quarter_end",  \''.$qDate.'\'+ INTERVAL 3 MONTH as "next_quarter_start" FROM '.
	' person_to_training as ptt JOIN training as t ON ptt.training_id = t.id ';
	$sql .= ' LEFT JOIN training_to_training_pepfar_categories_option as tpfr ON t.id = tpfr.training_id ';
	$sql .= ' LEFT JOIN training_pepfar_categories_option as pfr ON tpfr.training_pepfar_categories_option_id = pfr.id ';
	break;
	}

	if ( $district_id =  $this->getSanParam('district_id')) {
	$sql .= '  JOIN training_location as tl ON t.training_location_id = tl.id AND tl.location_district_id = '.$district_id;
	}
	else if ( $province_id = $this->getSanParam('province_id') ) {
	$sql .= '  JOIN training_location as tl ON t.training_location_id = tl.id AND tl.location_province_id = '.$province_id;
	}

	$sql .= ' WHERE training_start_date >= \''.$qDate.'\'  AND training_start_date < \''.$qDate.'\'+ INTERVAL 3 MONTH ';
	$sql .=	 ' GROUP BY cat ORDER BY cat ASC ';

	$rowArray = $db->fetchAll($sql);

	//add a total row
	if ( !$rowArray ) { //we always want the next start date
	$sql = 'SELECT 0 as "cnt", \'total\' as "cat", \''.$qDate.'\'+ INTERVAL 3 MONTH - INTERVAL 1 DAY as "quarter_end",  \''.$qDate.'\'+ INTERVAL 3 MONTH as "next_quarter_start"';
	$rowArray = $db->fetchAll($sql);
	} else {
	$total = 0;
	foreach($rowArray as $row) {
	$total += $row['cnt'];
	}

	$rowArray []= array('cat'=>'total', 'cnt'=>$total);
	$grandTotal += $total;
	}


	$results[$qDate] = $rowArray;
	$qDate = $rowArray[0]['next_quarter_start'];

	}
	if ( $this->getParam('outputType')  ) $this->sendData($results);
	}

	$this->view->assign('count',(isset($grandTotal)?$grandTotal:0) );
	$this->view->assign('results', $results);
	$this->view->assign('criteria',$criteria);
	}
	*/

	public function participantsByCategoryAction() {
		require_once ('views/helpers/Location.php');
		require_once ('views/helpers/DropDown.php');
		$criteria = array ();

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0 ";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = $parts [0];
		$criteria ['start-month'] = $parts [1];
		$criteria ['start-day'] = $parts [2];

		$criteria ['end-year'] = date ( 'Y' );
		$criteria ['end-month'] = date ( 'm' );
		$criteria ['end-day'] = date ( 'd' );

		$criteria ['cat'] = $this->getSanParam ( 'cat' );
		$criteria ['training_organizer_option_id'] = $this->getSanParam ( 'training_organizer_option_id' );
		$criteria ['qualification_option_id'] = $this->getSanParam ( 'qualification_option_id' );
		$criteria ['gender'] = $this->getSanParam ( 'gender' );
		$criteria ['age_min'] = $this->getSanParam ( 'age_min' );
		$criteria ['age_max'] = $this->getSanParam ( 'age_max' );
		$criteria ['showGender'] = $this->getSanParam ( 'showGender' );
		$criteria ['showQualification'] = $this->getSanParam ( 'showQualification' );
		$criteria ['showAge'] = $this->getSanParam ( 'showAge' );

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );

		$qDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
		$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];

		//locations
		$locations = Location::getAll();
		$this->view->assign('locations', $locations);

		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);

		$rowArray = array ();
		if ($this->getSanParam ( 'go' )) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();

			$selectFields = array ();
			if ($criteria ['training_organizer_option_id']) {
				$selectFields []= 'training_organizer_phrase';
			}
			if ($criteria ['showQualification']) {
				$selectFields []= 'pqo.qualification_phrase';
			}
			if ($criteria ['showAge']) {
				$selectFields []= 'CASE WHEN p.birthdate IS NULL OR p.birthdate = \'0000-00-00\' THEN NULL ELSE ((date_format(now(),\'%Y\') - date_format(p.birthdate,\'%Y\')) - (date_format(now(),\'00-%m-%d\') < date_format(p.birthdate,\'00-%m-%d\')) ) END as "age" ';
			}
			if ($criteria ['showGender']) {
				$selectFields []= 'CASE WHEN p.gender IS NULL THEN \'na\' WHEN p.gender = \'\' THEN \'na\' ELSE p.gender END as "gender" ';
			}

			if ($selectFields) {
				$selectFields = ', ' . implode ( ',', $selectFields );
			} else {
				$selectFields = '';
			}



			switch ($criteria ['cat']) {
				case 'level' :
					$sql = 'SELECT count(DISTINCT ptt.id) as "cnt", count(DISTINCT ptt.person_id) as "person_cnt", tlo.training_level_phrase as "cat" ' . $selectFields . ' FROM ' . ' person_to_training as ptt JOIN training as t ON ptt.training_id = t.id ';
					$sql .= '  JOIN person as p ON ptt.person_id = p.id ';
					$sql .= ' LEFT JOIN training_level_option as tlo ON t.training_level_option_id = tlo.id ';
				break;
				case 'qualification' :
					$sql = 'SELECT count(DISTINCT ptt.id) as "cnt", count(DISTINCT ptt.person_id) as "person_cnt", pqo.qualification_phrase as "cat" ' . $selectFields . ' FROM ' . ' person_to_training as ptt JOIN training as t ON ptt.training_id = t.id ';
					$sql .= '  JOIN person as p ON ptt.person_id = p.id ';
					//$sql .= ' LEFT JOIN person_qualification_option as pqo ON p.primary_qualification_option_id = pqo.id ';


					// primary qualifications only
					$sql .= '
					LEFT JOIN person_qualification_option as pqo ON
							(p.primary_qualification_option_id = pqo.id AND pqo.parent_id IS NULL)
						OR
							(pqo.id = (SELECT parent_id FROM person_qualification_option WHERE id = p.primary_qualification_option_id LIMIT 1))';
				break;

				case 'pepfar' :
					$sql = 'SELECT count(DISTINCT ptt.id) as "cnt", count(DISTINCT ptt.person_id) as "person_cnt", pfr.pepfar_category_phrase as "cat" ' . $selectFields . ' FROM ' . ' person_to_training as ptt JOIN training as t ON ptt.training_id = t.id ';
					$sql .= '  JOIN person as p ON ptt.person_id = p.id ';
					$sql .= ' LEFT JOIN training_to_training_pepfar_categories_option as tpfr ON t.id = tpfr.training_id ';
					$sql .= ' LEFT JOIN training_pepfar_categories_option as pfr ON tpfr.training_pepfar_categories_option_id = pfr.id ';
				break;
			}

			if ($criteria['cat'] != 'qualification' AND ($criteria['qualification_option_id'] OR $criteria['showQualification'])) {
				$sql .= '
					LEFT JOIN person_qualification_option as pqo ON (
						(p.primary_qualification_option_id = pqo.id AND pqo.parent_id IS NULL)
						OR
						pqo.id = (SELECT parent_id FROM person_qualification_option WHERE id = p.primary_qualification_option_id LIMIT 1)
					)';

			}

			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);

			if ($criteria ['district_id'] OR ! empty ( $criteria ['province_id'] ) OR ! empty ( $criteria ['region_c_id'] ) || $criteria['region_d_id'] || $criteria['region_e_id'] || $criteria['region_f_id'] || $criteria['region_g_id'] || $criteria['region_h_id'] || $criteria['region_i_id']) {
				$sql .= '  JOIN facility as f ON p.facility_id = f.id JOIN (' . $location_sub_query . ') as l ON  f.location_id = l.id ';
			}

			if ($criteria ['training_organizer_option_id']) {
				$sql .= '	JOIN training_organizer_option as torg ON torg.id = t.training_organizer_option_id ';
			}

			$sql .= ' WHERE training_start_date >= \'' . $qDate . '\'  AND training_start_date <= \'' . $endDate . '\' ';

			if ($criteria ['age_min']) {
				$sql .= ' AND "age" >= '.$criteria['age_min'];
			}
			if ($criteria ['age_max']) {
				$sql .= ' AND "age" <= '.$criteria['age_max'];
			}
			if ($criteria ['qualification_id']) {
				$sql .= ' AND (pqo.id = ' . $criteria ['qualification_id'] . ' OR pqo.parent_id = ' . $criteria ['qualification_id'] . ') ';
			}
			if ($criteria ['training_gender']) {
				$sql .= ' AND pt.gender = \'' . $criteria ['training_gender'] . '\'';
			}

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			if ($org_allowed_ids = allowed_org_access_full_list($this)) { // doesnt have acl 'training_organizer_option_all'
				$sql .= " AND training_organizer_option_id in ($org_allowed_ids) ";
			}
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); { // for sites to host multiple training organizers on one domain
				$sql .= $site_orgs ? " AND training_organizer_option_id in ($site_orgs) " : "";
			}

			if ($locWhere = $this->getLocationCriteriaWhereClause($criteria)) {
				$sql .= ' AND ' . $locWhere;
			}

			if ($criteria ['training_organizer_option_id'][0] && is_array ( $criteria ['training_organizer_option_id'] )) {
				$sql .= ' AND t.training_organizer_option_id IN (' . implode ( ',', $criteria ['training_organizer_option_id'] ) . ')';
			}

			$sql .= ' GROUP BY ';
			if ($criteria ['showAge']) {
				$sql .= 'age,';
			}
			if ($criteria ['showQualification']) {
				$sql .= 'qualification_phrase,';
			}
			if ($criteria ['showGender']) {
				$sql .= 'gender,';
			}
			$sql .= ' cat ';

			if ($criteria ['training_organizer_option_id'] && is_array ( $criteria ['training_organizer_option_id'] )) {
				$sql .= ', t.training_organizer_option_id ';
			}

			$sql .= ' ORDER BY cat ASC ';

			$rowArray = $db->fetchAll ( $sql );

			//add a total row
			$total = 0;
			foreach ( $rowArray as $row ) {
				$total += $row ['cnt'];
			}

			if ($this->getParam ( 'outputType' ))
				$this->sendData ( $this->reportHeaders ( false, $rowArray ) );
		}

		$this->view->assign ( 'count', (isset ( $total ) ? $total : 0) );
		$this->viewAssignEscaped ( 'results', $rowArray );
		$this->view->assign ( 'criteria', $criteria );

		//organizers
		$orgWhere = '';
		// restricted access?? only show trainings we have the ACL to view
		require_once('views/helpers/TrainingViewHelper.php');
		if ($org_allowed_ids = allowed_org_access_full_list($this)) { // doesnt have acl 'training_organizer_option_all'
			$orgWhere = " id in ($org_allowed_ids) ";
		}
		// restricted access?? only show organizers that belong to this site if its a multi org site		
		if ($site_orgs = allowed_organizer_in_this_site($this)) { // for sites to host multiple training organizers on one domain
			$orgWhere .= $orgWhere ? " AND id in ($site_orgs) " : " id in ($site_orgs) ";
		}

		$this->view->assign ( 'organizers_checkboxes', Checkboxes::generateHtml ( 'training_organizer_option', 'training_organizer_phrase', 
				$this->view, array(), $orgWhere) );
	//gnr
		$this->view->assign ( 'organizers_dropdown', DropDown::generateHtml ('training_organizer_option', 'training_organizer_phrase', 
				$criteria['training_organizer_option_id'], true, $this->view->viewonly, false,null,null,null,     true, 10 ) );

	}

	public function trainingByFacilityAction() {
		$this->view->assign ( 'mode', 'id' );
		$this->facilityReport ();
	}

	public function trainingByFacilityCountAction() {
		$this->view->assign ( 'mode', 'count' );
		$this->facilityReport ();
	}

	public function facilityReport() {

		require_once ('models/table/TrainingLocation.php');

		$criteria = array ();

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = $parts [0];
		$criteria ['start-month'] = $parts [1];
		$criteria ['start-day'] = $parts [2];

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		if ($this->view->mode == 'search') {
			$sql = "SELECT MAX(training_start_date) as \"start\" FROM training ";
			$rowArray = $db->fetchAll ( $sql );
			$end_default = $rowArray [0] ['start'];
			$parts = explode('-', $end_default );
			$criteria ['end-year'] = $parts [0];
			$criteria ['end-month'] = $parts [1];
			$criteria ['end-day'] = $parts [2];
		} else {
			$criteria ['end-year'] = date ( 'Y' );
			$criteria ['end-month'] = date ( 'm' );
			$criteria ['end-day'] = date ( 'd' );
		}

		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );

		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);

		$criteria ['training_title_option_id'] = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['training_title_id'] = $this->getSanParam ( 'training_title_id' );
		$criteria ['training_location_id'] = $this->getSanParam ( 'training_location_id' );
		$criteria ['training_organizer_id'] = $this->getSanParam ( 'training_organizer_id' );
		$criteria ['training_pepfar_id'] = $this->getSanParam ( 'training_pepfar_id' );
		$criteria ['training_topic_id'] = $this->getSanParam ( 'training_topic_id' );
		$criteria ['training_level_id'] = $this->getSanParam ( 'training_level_id' );
		$criteria ['facility_type_id'] = $this->getSanParam ( 'facility_type_id' );
		$criteria ['facility_sponsor_id'] = $this->getSanParam ( 'facility_sponsor_id' );
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['is_tot'] = $this->getSanParam ( 'is_tot' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );//TA:75 fixing filtering by qualification

		$criteria ['go'] = $this->getSanParam ( 'go' );
		$criteria ['doCount'] = ($this->view->mode == 'count');
		$criteria ['showProvince'] = ($this->getSanParam ( 'showProvince' ) or ($criteria ['doCount'] and ($criteria ['province_id'] or $criteria ['province_id'] === '0')));
		$criteria ['showDistrict'] = ($this->getSanParam ( 'showDistrict' ) or ($criteria ['doCount'] and ($criteria ['district_id'] or $criteria ['district_id'] === '0')));
		$criteria ['showRegionC'] = ($this->getSanParam ( 'showRegionC' ) or ($criteria ['doCount'] and ($criteria ['region_c_id'] or $criteria ['region_c_id'] === '0')));
		$criteria ['showTrainingTitle'] = ($this->getSanParam ( 'showTrainingTitle' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0' or $criteria ['training_title_id'])));
		$criteria ['showLocation'] = ($this->getSanParam ( 'showLocation' ) or ($criteria ['doCount'] and $criteria ['training_location_id']));
		$criteria ['showOrganizer'] = ($this->getSanParam ( 'showOrganizer' ) or ($criteria ['doCount'] and ($criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0')));
		$criteria ['showPepfar'] = ($this->getSanParam ( 'showPepfar' ) or ($criteria ['doCount'] and ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0')));
		$criteria ['showTopic'] = ($this->getSanParam ( 'showTopic' ) or ($criteria ['doCount'] and ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')));
		$criteria ['showLevel'] = ($this->getSanParam ( 'showLevel' ) or ($criteria ['doCount'] and $criteria ['training_level_id']));
		$criteria ['showType'] = ($this->getSanParam ( 'showType' ) or ($criteria ['doCount'] and ($criteria ['facility_type_id'] or $criteria ['facility_type_id'] === '0')));
		$criteria ['showSponsor'] = ($this->getSanParam ( 'showSponsor' ) or ($criteria ['doCount'] and $criteria ['facility_sponsor_id']));
		$criteria ['showFacility'] =       true;
		$criteria ['showTot'] = ($this->getSanParam ( 'showTot' ) or ($criteria ['doCount'] and $criteria ['is_tot'] !== '' or $criteria ['is_tot'] === '0'));
		$criteria ['age_min'] =            $this->getSanParam ( 'age_min' );
		$criteria ['age_max'] =            $this->getSanParam ( 'age_max' );
		$criteria ['training_gender']    = $this->getSanParam ( 'training_gender' );
		$criteria ['qualification_id']   = $this->getSanParam ( 'qualification_id' );
		$criteria ['showAge']            = ( $this->getSanParam ( 'showAge' )    or ($criteria ['doCount'] and ($criteria['age_min'] or $criteria['age_max'])) );
		$criteria ['showGender']         = ( $this->getSanParam ( 'showGender' ) or ($criteria ['doCount'] and $criteria ['training_gender']) );
		$criteria ['showQual']           = ( $this->getSanParam ( 'showQual' )   or ($criteria ['doCount'] and $criteria ['qualification_id']) );
		if ($criteria ['go']) {

			$sql = 'SELECT '; //todo test

			if ($criteria ['doCount']) {
				$sql .= ' COUNT(pt.person_id) as "cnt", pt.facility_name ';
			} else {
				$sql .= ' DISTINCT pt.id as "id", pt.facility_name, pt.training_start_date  ';
			}
			if ($criteria ['showFacility']) {
				$sql .= ', pt.facility_name ';
			}

			if ($criteria ['showTrainingTitle'] or ($this->view->mode == 'search')) {
				$sql .= ', pt.training_title ';
			}
			if ($criteria ['showDistrict']) {
				$sql .= ', pt.district_name, pt.district_id ';
			}
			if ($criteria ['showProvince']) {
				$sql .= ', pt.province_name, pt.province_id ';
			}
			if ($criteria ['showRegionC']) {
				$sql .= ', pt.region_c_name, pt.region_c_id ';
			}
			if ($criteria ['showLocation']) {
				$sql .= ', pt.training_location_name ';
			}

			if ($criteria ['showOrganizer']) {
				$sql .= ', torg.training_organizer_phrase ';
			}

			if ($criteria ['showLevel']) {
				$sql .= ', tlev.training_level_phrase ';
			}

			if ($criteria ['showType']) {
				$sql .= ', fto.facility_type_phrase ';
			}

			if ($criteria ['showSponsor']) {
				$sql .= ', fso.facility_sponsor_phrase ';
			}

			if ($criteria ['showPepfar']) {
				if ($criteria ['doCount']) {
					$sql .= ', tpep.pepfar_category_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT tpep.pepfar_category_phrase) as "pepfar_category_phrase" ';
				}
			}

			if ($criteria ['showTopic']) {
				if ($criteria ['doCount']) {
					$sql .= ', ttopic.training_topic_phrase ';
				} else {
					$sql .= ', GROUP_CONCAT(DISTINCT ttopic.training_topic_phrase ORDER BY training_topic_phrase) AS "training_topic_phrase" ';
				}
			}

			if ($criteria ['showTot']) {
				//$sql .= ', pt.is_tot ';
				$sql .= ", IF(pt.is_tot,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_tot";
			}
			if ($criteria ['showAge']) {
				$sql .= ', CASE WHEN birthdate  IS NULL OR birthdate = \'0000-00-00\' THEN NULL ELSE ((date_format(now(),\'%Y\') - date_format(birthdate,\'%Y\')) - (date_format(now(),\'00-%m-%d\') < date_format(birthdate,\'00-%m-%d\')) ) END as "age" ';
			}
			if ($criteria ['showGender']) {
				$sql .= ' , CASE WHEN gender IS NULL THEN \'na\' WHEN gender = \'\' THEN \'na\' ELSE gender END as "gender" ';
			}
			if ($criteria ['showQual']) {
				$sql .= ', qualification_phrase ';
			}

			//JOIN with the participants to get facility

			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);

			if ($criteria ['doCount']) {
				$sql .= ' FROM (SELECT training.*, fac.person_id as "person_id", fac.facility_id as "facility_id", fac.type_option_id, fac.sponsor_option_id, fac.facility_name as "facility_name" , tto.training_title_phrase AS training_title,training_location.training_location_name,birthdate,gender,qualification_phrase, l.'.implode(', l.',$field_name).'
				       FROM training
				         JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id)
				         JOIN training_location ON training.training_location_id = training_location.id
				         JOIN (SELECT person_id, facility_name, facility_id, location_id, type_option_id, sponsor_option_id,training_id,birthdate,gender,qualification_phrase
					            FROM person
				               JOIN person_to_training ON person_to_training.person_id = person.id
				               JOIN facility as f ON person.facility_id = f.id
				               JOIN person_qualification_option qual ON qual.id = person.primary_qualification_option_id ';
				    //TA:75 fixing filtering by qualification
				    if($criteria ['qualification_id'] && $criteria ['qualification_id'] !== ''){
				        $sql .=  ' where qual.id = ' . $criteria ['qualification_id'];
				    }
				
				   $sql .= ' ) as fac ON training.id = fac.training_id
				         LEFT JOIN ('.$location_sub_query.') as l ON fac.location_id = l.id WHERE training.is_deleted=0) as pt ';
			} else {
				$sql .= ' FROM (SELECT training.*, fac.facility_id as "facility_id", fac.type_option_id, fac.sponsor_option_id ,fac.facility_name as "facility_name" , tto.training_title_phrase AS training_title,training_location.training_location_name, l.'.implode(', l.',$field_name).
				'       FROM training  ' .
				'         JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id) ' .
				'         JOIN training_location ON training.training_location_id = training_location.id ' .
				'         JOIN (SELECT DISTINCT facility_name, facility_id, location_id, training_id, type_option_id, sponsor_option_id FROM person JOIN person_to_training ON person_to_training.person_id = person.id '.
				'         LEFT JOIN facility as f ON person.facility_id = f.id) as fac ON training.id = fac.training_id LEFT JOIN ('.$location_sub_query.') as l ON fac.location_id = l.id  WHERE training.is_deleted=0) as pt ';
			}

			if ($criteria ['showOrganizer']) {
				$sql .= '	JOIN training_organizer_option as torg ON torg.id = pt.training_organizer_option_id ';
			}
			if ($criteria ['showLevel']) {
				$sql .= '	JOIN training_level_option as tlev ON tlev.id = pt.training_level_option_id ';
			}

			if ($criteria ['showType']) {
				$sql .= '	JOIN facility_type_option as fto ON fto.id = pt.type_option_id ';
			}

			if ($criteria ['showSponsor']) {
				$sql .= '	JOIN facility_sponsor_option as fso ON fso.id = pt.sponsor_option_id ';
			}

			if ($criteria ['showPepfar']) {
				$sql .= '	LEFT JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';
			}

			//TA:72
			//if ($criteria ['showTopic']) {
			if ($criteria ['showTopic'] or $criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				$sql .= '	LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';
			}

			$where = array(' pt.is_deleted=0 ');

			if ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0') {
				$where []= ' pt.training_title_option_id = ' . $criteria ['training_title_option_id'];
			}

			if ($criteria ['training_title_id'] or $criteria ['training_title_id'] === '0') {
				$where []= ' pt.training_title_option_id = ' . $criteria ['training_title_id'];
			}

			if ($criteria ['facilityInput']) {
				$where []= ' pt.facility_id = \'' . $criteria ['facilityInput'] . '\'';
			}

			if ($criteria ['training_location_id']) {
				$where []= ' pt.training_location_id = \'' . $criteria ['training_location_id'] . '\'';
			}

			if ($criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0') {
				$where []= ' pt.training_organizer_option_id = \'' . $criteria ['training_organizer_id'] . '\'';
			}

			if ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				$where []= ' ttopic.training_topic_option_id = \'' . $criteria ['training_topic_id'] . '\'';
			}

			if ($criteria ['facility_type_id'] or $criteria ['facility_type_id'] === '0') {
				$where []= ' pt.type_option_id = \'' . $criteria ['facility_type_id'] . '\'';
			}
			if ($criteria ['facility_sponsor_id'] or $criteria ['facility_sponsor_id'] === '0') {
				$where []= ' pt.sponsor_option_id = \'' . $criteria ['facility_sponsor_id'] . '\'';
			}

			if ($criteria ['training_level_id']) {
				$where []= ' pt.training_level_option_id = \'' . $criteria ['training_level_id'] . '\'';
			}

			if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0') {
				$where []= ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';
			}

			if (intval ( $criteria ['end-year'] ) and $criteria ['start-year']) {
				$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where []= ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
			}

			if ($criteria ['is_tot'] or $criteria ['is_tot'] === '0') {
				$where []= ' pt.is_tot = ' . $criteria ['is_tot'];
			}
			
			//TA:71
			if ($criteria ['showProvince']) {
			    $where []= ' pt.province_name IS NOT NULL ';
			}

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where []= " training_organizer_option_id in ($org_allowed_ids) ";
			}
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			if ($site_orgs)
				$where []= " training_organizer_option_id in ($site_orgs) ";

			if ($where)
			$sql .= ' WHERE ' . implode(' AND ', $where);

			if ($criteria ['doCount']) {

				$groupBy = array();
				if ($criteria ['showFacility'])      $groupBy []= '  pt.facility_id';
				if ($criteria ['showTrainingTitle']) $groupBy []= ' pt.training_title_option_id';
				if ($criteria ['showProvince'])      $groupBy []= ' pt.province_id';
				if ($criteria ['showDistrict'])      $groupBy []= '  pt.district_id';
				if ($criteria ['showRegionC'])       $groupBy []= '  pt.region_c_id';
				if ($criteria ['showLocation'])      $groupBy []= '  pt.training_location_id';
				if ($criteria ['showOrganizer'])     $groupBy []= '  pt.training_organizer_option_id';
				if ($criteria ['showTopic'])         $groupBy []= '  ttopic.training_topic_option_id';
				if ($criteria ['showLevel'])         $groupBy []= '  pt.training_level_option_id';
				if ($criteria ['showPepfar'])        $groupBy []= '  tpep.training_pepfar_categories_option_id';
				if ($criteria ['showType'])          $groupBy []= '  pt.type_option_id';
				if ($criteria ['showSponsor'])       $groupBy []= '  pt.sponsor_option_id';
				if ($criteria ['showTot'])           $groupBy []= '  pt.is_tot';
				if ( $criteria['showAge'])           $groupBy []= '  age ';
				if ($criteria ['showGender'])        $groupBy []= '  pt.gender';
				if ($criteria ['showQual'])          $groupBy []= '  pt.qualification_phrase ';


				if ($groupBy)
				$groupBy = ' GROUP BY ' . implode(', ',$groupBy);
				$sql .= $groupBy;
			} else {
				if ($criteria ['showPepfar'] || $criteria ['showTopic']) {
					$sql .= ' GROUP BY pt.id';
				}
			}
			
			//print $sql;

			$rowArray = $db->fetchAll ( $sql . ' ORDER BY facility_name ASC ' );

			if ($criteria ['doCount']) {
				$count = 0;
				foreach ( $rowArray as $row ) {
					$count += $row ['cnt'];
				}
			} else {
				$count = count ( $rowArray );
			}

			if ($this->getParam ( 'outputType' ))
			$this->sendData ( $this->reportHeaders ( false, $rowArray ) );

		} else {
			$count = 0;
			$rowArray = array ();
		}

		$criteria ['go'] = $this->getSanParam ( 'go' );

		//not sure why we are getting multiple PEPFARS
		foreach ( $rowArray as $key => $row ) {
			if (isset ( $row ['pepfar_category_phrase'] )) {
				$rowArray [$key] ['pepfar_category_phrase'] = implode ( ',', array_unique ( explode(',', $row ['pepfar_category_phrase'] ) ) );
			}
		}

		$this->viewAssignEscaped ( 'results', $rowArray );
		$this->view->assign ( 'count', $count );
		$this->view->assign ( 'criteria', $criteria );

		//facilities list
		$fArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $fArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );

		//locations
		$locations = Location::getAll();
		$this->viewAssignEscaped ( 'locations', $locations );
		//facility types
		$typesArray = OptionList::suggestionList ( 'facility_type_option', 'facility_type_phrase', false, false );
		$this->viewAssignEscaped ( 'facility_types', $typesArray );
		//sponsor types
		$sponsorsArray = OptionList::suggestionList ( 'facility_sponsor_option', 'facility_sponsor_phrase', false, false );
		$this->viewAssignEscaped ( 'facility_sponsors', $sponsorsArray );

		//course
		$courseArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $courseArray );

		//location
		$tlocations = TrainingLocation::selectAllLocations ($this->setting('num_location_tiers'));
		$this->viewAssignEscaped ( 'tlocations', $tlocations );
		//organizers
		$organizersArray = OptionList::suggestionList ( 'training_organizer_option', 'training_organizer_phrase', false, false, false );
		$this->viewAssignEscaped ( 'organizers', $organizersArray );
		//topics
		$topicsArray = OptionList::suggestionList ( 'training_topic_option', 'training_topic_phrase', false, false, false );
		$this->viewAssignEscaped ( 'topics', $topicsArray );
		//levels
		$levelArray = OptionList::suggestionList ( 'training_level_option', 'training_level_phrase', false, false );
		$this->viewAssignEscaped ( 'levels', $levelArray );
		//pepfar
		$organizersArray = OptionList::suggestionList ( 'training_pepfar_categories_option', 'pepfar_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'pepfars', $organizersArray );

		//facilities list
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );

		// qualifications
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false, array ('0 AS is_default', 'child.is_default' ) );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );

	}

	//
	// Recommended Classes
	//


	public function needsReport() {
		require_once ('models/table/TrainingRecommend.php');
		require_once ('models/table/TrainingTitleOption.php');

		$criteria = array ();

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = '0000-00-00';
		if ($rowArray and $rowArray [0] ['start'])
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = $parts [0];
		$criteria ['start-month'] = $parts [1];
		$criteria ['start-day'] = $parts [2];

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		$criteria ['end-year'] = date ( 'Y' );
		$criteria ['end-month'] = date ( 'm' );
		$criteria ['end-day'] = date ( 'd' );
		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );

		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);

		$criteria ['training_gender'] = $this->getSanParam ( 'training_gender' );
		$criteria ['training_active'] = $this->getSanParam ( 'training_active' );
		$criteria ['concatNames'] = $this->getSanParam ( 'concatNames' );
		$criteria ['training_title_option_id'] = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['training_title_id'] = $this->getSanParam ( 'training_title_id' );
		$criteria ['course_recommend_id'] = $this->getSanParam ( 'course_recommend_id' );
		$criteria ['training_pepfar_id'] = $this->getSanParam ( 'training_pepfar_id' );
		$criteria ['training_topic_id'] = $this->getSanParam ( 'training_topic_id' );
		$criteria ['training_topic_recommend_id'] = $this->getSanParam ( 'training_topic_recommend_id' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['qualification_secondary_id'] = $this->getSanParam ( 'qualification_secondary_id' );
		$criteria ['upcoming_id'] = $this->getSanParam ( 'upcoming_id' );
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['first_name'] = $this->getSanParam ( 'first_name' );
		$criteria ['last_name'] = $this->getSanParam ( 'last_name' );

		$criteria ['doCount'] = ($this->view->mode == 'count');
		$criteria ['showProvince'] = ($this->getSanParam ( 'showProvince' ) or ($criteria ['doCount'] and ($criteria ['province_id'] or $criteria ['province_id'] === '0')));
		$criteria ['showDistrict'] = ($this->getSanParam ( 'showDistrict' ) or ($criteria ['doCount'] and ($criteria ['district_id'] or $criteria ['district_id'] === '0')));
		$criteria ['showRegionC'] = ($this->getSanParam ( 'showRegionC' ) or ($criteria ['doCount'] and ($criteria ['region_c_id'] or $criteria ['region_c_id'] === '0')));
		$criteria ['showTrainingTitle'] = ($this->getSanParam ( 'showTrainingTitle' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0' or $criteria ['training_title_id'])));
		$criteria ['showPepfar'] = ($this->getSanParam ( 'showPepfar' ) or ($criteria ['doCount'] and ($criteria ['training_title_option_id'] or $criteria ['training_pepfar_id'] === '0' or $criteria ['training_title_id'])));
		$criteria ['showQualPrim'] = ($this->getSanParam ( 'showQualPrim' ) or ($criteria ['doCount'] and ($criteria ['qualification_id'] or $criteria ['qualification_id'] === '0')));
		$criteria ['showQualSecond'] = ($this->getSanParam ( 'showQualSecond' ) or ($criteria ['doCount'] and ($criteria ['qualification_secondary_id'] or $criteria ['qualification_secondary_id'] === '0')));
		$criteria ['showTopic'] = ($this->getSanParam ( 'showTopic' ) or ($criteria ['doCount'] and ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')));
		$criteria ['showTopicRecommend'] = ($this->getSanParam ( 'showTopicRecommend' ) or ($criteria ['doCount'] and ($criteria ['training_topic_recommend_id'] or $criteria ['training_topic_recommend_id'] === '0')));
		$criteria ['showCourseRecommend'] = ($this->getSanParam ( 'showCourseRecommend' ) or ($criteria ['doCount'] and ($criteria ['course_recommend_id'] or $criteria ['course_recommend_id'] === '0')));
		$criteria ['showFacility'] = ($this->getSanParam ( 'showFacility' ) or ($criteria ['doCount'] and $criteria ['facility_name']));
		$criteria ['showGender'] = ($this->getSanParam ( 'showGender' ) or ($criteria ['doCount'] and $criteria ['training_gender']));
		$criteria ['showActive'] = ($this->getSanParam ( 'showActive' ) or ($criteria ['doCount'] and $criteria ['training_active']));
		$criteria ['showEmail'] = ($this->getSanParam ( 'showEmail' ));
		$criteria ['showPhone'] = ($this->getSanParam ( 'showPhone' ));
		$criteria ['showClasses'] = ($this->getSanParam ( 'showPhone' ));
		$criteria ['showUpcoming'] = ($this->getSanParam ( 'showUpcoming' ));

		$criteria ['showFirstName'] = ($this->getSanParam ( 'showFirstName' ));
		$criteria ['showLastName'] = ($this->getSanParam ( 'showLastName' ));

		$criteria ['go'] = $this->getSanParam ( 'go' );
		if ($criteria ['go']) {

			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id, true);

			$sql = 'SELECT ';

			if ($criteria ['doCount']) {
				$sql .= ' COUNT(pt.person_id) as "cnt" ';
			} else {
				if ($criteria ['concatNames'])
				$sql .= ' DISTINCT pt.person_id as "id", CONCAT(first_name, ' . "' '" . ',last_name, ' . "' '" . ', IFNULL(suffix_phrase, ' . "' '" . ')) as "name", IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, pt.training_start_date  ';
				else
				$sql .= ' DISTINCT pt.person_id as "id", IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, last_name, first_name, middle_name, pt.training_start_date  ';
			}
			if ($criteria ['showPhone']) {
				$sql .= ", CASE WHEN (pt.phone_work IS NULL OR pt.phone_work = '') THEN NULL ELSE pt.phone_work END as \"phone_work\", CASE WHEN (pt.phone_home IS NULL OR pt.phone_home = '') THEN NULL ELSE pt.phone_home END as \"phone_home\", CASE WHEN (pt.phone_mobile IS NULL OR pt.phone_mobile = '') THEN NULL ELSE pt.phone_mobile END as \"phone_mobile\" ";
			}
			if ($criteria ['showEmail']) {
				$sql .= ', pt.email ';
			}
			if ($criteria ['showGender']) {
				$sql .= ', pt.gender ';
			}
			if ($criteria ['showActive']) {
				$sql .= ', pt.active ';
			}
			if ($criteria ['showTrainingTitle']) {
				$sql .= ', pt.training_title, pt.training_title_option_id '; // already GROUP_CONCAT'ed in main SQL
			}
			if ($criteria ['showDistrict']) {
				$sql .= ', pt.district_name ';
			}
			if ($criteria ['showProvince']) {
				$sql .= ', pt.province_name ';
			}
			if ($criteria ['showRegionC']) {
				$sql .= ', pt.region_c_name ';
			}

			if ($criteria ['showPepfar']) {
				$sql .= ', tpep.pepfar_category_phrase ';
			}

			if ($criteria ['showTopic']) {
				$sql .= ', ttopic.training_topic_phrase ';
			}

			if ($criteria ['showFacility']) {
				$sql .= ', pt.facility_name ';
			}

			if ($criteria ['showQualPrim']) {
				$sql .= ', pq.qualification_phrase ';
			}
			if ($criteria ['showQualSecond']) {
				$sql .= ', pqs.qualification_phrase AS qualification_secondary_phrase';
			}

			// NOT USED! (recommended topics are, though)
			if ((! $criteria ['doCount']) and $criteria ['showUpcoming']) {
				//$sql .= ', precommend.training_title_phrase AS recommended';
				$sql .= ", GROUP_CONCAT(DISTINCT CONCAT(recommend.training_title_phrase ) ORDER BY training_title_phrase SEPARATOR ', ') AS upcoming ";

			}

			if ($criteria ['showTopicRecommend']) {
				//$sql .= ', ptopic.training_topic_phrase AS recommended ';


				$sql .= ", GROUP_CONCAT(DISTINCT CONCAT(ptopic.training_topic_phrase) ORDER BY training_topic_phrase SEPARATOR ', ') AS recommended ";

				// same training location? --  AND t.training_location_id = pt.training_location_id


			}

			// select everyone, not just participants
			$sql .= ' FROM (
			SELECT training.*, person.facility_id as "facility_id", person.id as "person_id", person.last_name, IFNULL(suffix_phrase, ' . "' '" . ') as suffix_phrase, person.first_name, person.middle_name, person.phone_work, person.phone_home, person.phone_mobile, person.email, CASE WHEN person.active = \'deceased\' THEN \'inactive\' ELSE person.active END as "active", CASE WHEN person.gender IS NULL THEN \'na\' WHEN person.gender = \'\' THEN \'na\' ELSE person.gender END as "gender",
			primary_qualification_option_id,facility.facility_name, l.'.implode(', l.',$field_name).
			', GROUP_CONCAT(DISTINCT CONCAT(training_title_phrase) ORDER BY training_title_phrase SEPARATOR \', \') AS training_title
			FROM person
			LEFT JOIN person_to_training ON person.id = person_to_training.person_id
			LEFT JOIN training ON training.id = person_to_training.training_id
			LEFT JOIN facility ON person.facility_id = facility.id
			LEFT JOIN ('.$location_sub_query.') as l ON facility.location_id = l.id
			LEFT JOIN training_title_option tto ON `training`.training_title_option_id = tto.id
			LEFT  JOIN person_suffix_option suffix ON person.suffix_option_id = suffix.id
			GROUP BY person.id
			) as pt ';

			if ($criteria ['showPepfar']) {
				$sql .= '	JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';
			}

			if ($criteria ['showTopic']) {
				$sql .= '	LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';
			}
			// Recommended classes
			if ((! $criteria ['doCount']) and ($criteria ['showUpcoming'] or $criteria ['upcoming_id'])) {
				// not tested
				$sql .= ($criteria ['upcoming_id'] ? "INNER" : "LEFT") . " JOIN (SELECT training_title_phrase, person_id
				FROM person_to_training_topic_option as ptto
				JOIN training_to_training_topic_option ttt ON (ttt.training_topic_option_id = ptto.training_topic_option_id)
				JOIN training t ON (t.id = ttt.training_id)
				JOIN training_title_option tt ON (tt.id = t.training_title_option_id)
				WHERE
				t.is_deleted = 0 AND tt.training_title_phrase != 'unknown' AND t.training_start_date > NOW()
				" . (($criteria ['upcoming_id']) ? ' AND t.training_title_option_id = ' . $criteria ['upcoming_id'] : '') . "
				) AS recommend ON recommend.person_id = pt.person_id ";

				//$sql .= ' JOIN person_to_training_topic_option ptto ON ptto.person_id = pt.person_id';
			}

			if ($criteria ['showTopicRecommend'] or $criteria ['training_topic_recommend_id'] or $criteria ['training_topic_id'] or ($criteria ['training_topic_id'] === '0')) {

				$sql .= '
				INNER JOIN (
				SELECT person_id, topicid.id, training_topic_phrase
				FROM person_to_training_topic_option ptto
				INNER JOIN training_topic_option topicid ON (topicid.id = ptto.training_topic_option_id)
				) AS ptopic ON ptopic.person_id = pt.person_id
				';


			}
			if ($criteria ['showQualPrim'] || $criteria ['showQualSecond'] || $criteria ['qualification_id'] || $criteria ['qualification_secondary_id']) {
				// primary qualifications
				$sql .= '
				LEFT JOIN person_qualification_option as pq ON (
				(pt.primary_qualification_option_id = pq.id AND pq.parent_id IS NULL)
				OR
				pq.id = (SELECT parent_id FROM person_qualification_option WHERE id = pt.primary_qualification_option_id LIMIT 1)
				)';

				// secondary qualifications
				$sql .= '
				LEFT JOIN person_qualification_option as pqs ON (
				pt.primary_qualification_option_id = pqs.id AND pqs.parent_id IS NOT NULL
				)';
			}

			$where = '';

			// legacy
			if ($criteria ['training_title_option_id'] or $criteria ['training_title_option_id'] === '0') {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' pt.training_title_option_id = ' . $criteria ['training_title_option_id'];
			}

			if ($criteria ['training_title_id'] or $criteria ['training_title_id'] === '0') {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' pt.training_title_option_id = ' . $criteria ['training_title_id'];
			}

			if ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0') {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' ttopic.training_topic_option_id = \'' . $criteria ['training_topic_id'] . '\'';
			}

			if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0') {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';
			}

			if ($criteria ['facilityInput']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' pt.facility_id = \'' . $criteria ['facilityInput'] . '\'';
			}

			if ($criteria ['training_gender']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' pt.gender = \'' . $criteria ['training_gender'] . '\'';
			}

			if ($criteria ['training_active']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' pt.active = \'' . $criteria ['training_active'] . '\'';
			}

			if ($criteria ['qualification_id']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' (pq.id = ' . $criteria ['qualification_id'] . ' OR pqs.parent_id = ' . $criteria ['qualification_id'] . ') ';
			}
			if ($criteria ['qualification_secondary_id']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= ' pqs.id = ' . $criteria ['qualification_secondary_id'];
			}

			if ($criteria ['training_topic_recommend_id']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= '  ptopic.id = ' . $criteria ['training_topic_recommend_id'];
			}
			if ($criteria ['first_name']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= $db->quoteInto(" first_name = ?", $criteria['first_name']);
			}
			if ($criteria ['last_name']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$where .= $db->quoteInto(" last_name = ?", $criteria['last_name']);
			}

			if (intval ( $criteria ['end-year'] ) and $criteria ['start-year']) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where .= ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
			}

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where .= " AND training_organizer_option_id in ($org_allowed_ids) ";
			}
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			$where .= $site_orgs ? " AND training_organizer_option_id in ($site_orgs) " : "";

			if ($where)
			$sql .= ' WHERE ' . $where;

			if ($criteria ['doCount']) {

				$groupBy = '';

				if ($criteria ['showTrainingTitle']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= ' pt.training_title_option_id';
				}
				if ($criteria ['showGender']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= ' pt.gender';
				}
				if ($criteria ['showActive']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= ' pt.active';
				}
				if ($criteria ['showProvince']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= ' pt.province_id';
				}
				if ($criteria ['showDistrict']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  pt.district_id';
				}
				if (isset ( $criteria ['showLocation'] ) and $criteria ['showLocation']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  pt.training_location_id';
				}
				if ($criteria ['showTopic']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  ttopic.training_topic_option_id';
				}

				if ($criteria ['showTopicRecommend']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  ptopic.id';
				}

				if ($criteria ['showQualPrim'] and ! $criteria ['showQualSecond']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  pq.id'; //added ToddW 090827
				} else if ($criteria ['showQualPrim'] || $criteria ['showQualSecond']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  pt.primary_qualification_option_id';
				}

				/*
				if ( $criteria['showQualPrim']) {
				if ( strlen($groupBy) ) $groupBy .= ' , ';
				//$groupBy .=	'  pq.id ';
				}
				if ( $criteria['showQualSecond']) {
				if ( strlen($groupBy) ) $groupBy .= ' , ';
				//$groupBy .=	'  pqs.id ';
				}
				*/

				if ($criteria ['showPepfar']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  tpep.training_pepfar_categories_option_id';
				}

				if ($criteria ['showFacility']) {
					if (strlen ( $groupBy ))
					$groupBy .= ' , ';
					$groupBy .= '  pt.facility_id';
				}

				if ($groupBy != '')
				$groupBy = ' GROUP BY ' . $groupBy;
				$sql .= $groupBy;
			} else {
				//if ( $criteria['showTopicRecommend'] || $criteria['showCourseRecommend']) {
				$sql .= ' GROUP BY pt.person_id, pt.id'; //added ToddW 090827 -- always group by person
				//}
			}

			//echo $sql; exit;


			$rowArray = $db->fetchAll ( $sql );

			if ($criteria ['doCount']) {
				$count = 0;
				foreach ( $rowArray as $row ) {
					$count += $row ['cnt'];
				}
			} else {
				$count = count ( $rowArray );
				//cheezy
				//get the count of people, now group by topic and run the query again
				//so we get a line for each topic
				if ($criteria ['showTopicRecommend']) {
					$sql .= ',ptopic.training_topic_phrase';
					$rowArray = $db->fetchAll ( $sql );
				}
			}
			if ($this->getParam ( 'outputType' ))
			$this->sendData ( $this->reportHeaders ( false, $rowArray ) );

		} else {
			$count = 0;
			$rowArray = array ();
		}

		$criteria ['go'] = $this->getSanParam ( 'go' );

		$this->viewAssignEscaped ( 'results', $rowArray );
		if ($rowArray) {
			$first = reset ( $rowArray );
			if (isset ( $first ['phone_work'] )) {
				foreach ( $rowArray as $key => $val ) {
					$phones = array ();
					if ($val ['phone_work'])
					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_work'] ) ) . '&nbsp;(w)';
					if ($val ['phone_home'])
					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_home'] ) ) . '&nbsp;(h)';
					if ($val ['phone_mobile'])
					$phones [] = str_replace ( ' ', '&nbsp;', trim ( $val ['phone_mobile'] ) ) . '&nbsp;(m)';
					$rowArray [$key] ['phone'] = implode ( ', ', $phones );
				}
				$this->view->assign ( 'results', $rowArray );
			}
		}

		$this->view->assign ( 'count', $count );
		$this->view->assign ( 'criteria', $criteria );

		//province
		/*
		$provinceArray = OptionList::suggestionList ( 'location_province', 'province_name', false, false, false );
		$this->viewAssignEscaped ( 'provinces', $provinceArray );
		//district
		$districtArray = OptionList::suggestionList ( 'location_district', array ('district_name', 'parent_province_id' ), false, false, false );
		$this->viewAssignEscaped ( 'districts', $districtArray );
		*/
		$locations = Location::getAll();
		$this->viewAssignEscaped('locations',$locations);

		//course
		$courseArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $courseArray );
		//topics
		$topicsArray = OptionList::suggestionList ( 'training_topic_option', 'training_topic_phrase', false, false, false );
		$this->viewAssignEscaped ( 'topics', $topicsArray );
		//qualifications (primary)
		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NULL' );
		$this->viewAssignEscaped ( 'qualifications_primary', $qualsArray );
		//qualifications (secondary)
		$qualsArray = OptionList::suggestionList ( 'person_qualification_option', 'qualification_phrase', false, false, false, 'parent_id IS NOT NULL' );
		$this->viewAssignEscaped ( 'qualifications_secondary', $qualsArray );
		//pepfar
		$organizersArray = OptionList::suggestionList ( 'training_pepfar_categories_option', 'pepfar_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'pepfars', $organizersArray );
		//upcoming classes
		if (! $criteria ['doCount']) {
			$upcomingArray = TrainingRecommend::getUpcomingTrainingTitles ();
			$this->viewAssignEscaped ( 'upcoming', $upcomingArray );
		}

		//recommended
		require_once 'models/table/TrainingRecommend.php';
		$topicsRecommend = TrainingRecommend::getTopics ();
		$this->viewAssignEscaped ( 'topicsRecommend', @$topicsRecommend->ToArray () );
		//facilities list
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
			$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );

	}

	public function needsByPersonCountAction() {
		$this->view->assign ( 'mode', 'count' );
		return $this->needsReport ();
	}

	public function needsByPersonNameAction() {
		return $this->needsReport ();
	}

	public function rosterAction() {
		#ini_set('max_execution_time','120'); // these are now exceeded globally
		#ini_set('memory_limit', '256M');
		$criteria ['training_organizer_id'] = $this->getSanParam ( 'training_organizer_id' );
		$criteria ['training_title_id'] = $this->getParam ( 'training_title_id' );
		$criteria ['is_extended'] = $is_extended = $this->getSanParam ( 'is_extended' );
		$criteria ['add_additional'] = $add_additional = $this->getSanParam ( 'add_additional' );
		$criteria ['go'] = $this->getSanParam('go');

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(training_start_date) as \"start\" FROM training WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = $rowArray [0] ['start'];
		$parts = explode('-', $start_default );
		$criteria ['start-year'] = $parts [0];
		$criteria ['start-month'] = $parts [1];
		$criteria ['start-day'] = $parts [2];

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		if ($this->view->mode == 'search') {
			$sql = "SELECT MAX(training_start_date) as \"start\" FROM training ";
			$rowArray = $db->fetchAll ( $sql );
			$end_default = $rowArray [0] ['start'];
			$parts = explode('-', $end_default );
			$criteria ['end-year'] = $parts [0];
			$criteria ['end-month'] = $parts [1];
			$criteria ['end-day'] = $parts [2];
		} else {
			$criteria ['end-year'] = date ( 'Y' );
			$criteria ['end-month'] = date ( 'm' );
			$criteria ['end-day'] = date ( 'd' );
		}

		if ($this->getSanParam ( 'end-year' ))
		$criteria ['end-year'] = $this->getSanParam ( 'end-year' );
		if ($this->getSanParam ( 'end-month' ))
		$criteria ['end-month'] = $this->getSanParam ( 'end-month' );
		if ($this->getSanParam ( 'end-day' ))
		$criteria ['end-day'] = $this->getSanParam ( 'end-day' );

		if ($criteria['go'])
		{
			try{
			// select trainings
			$sql = "SELECT id FROM training ";
			$where = "WHERE is_deleted=0";
			// where

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where .= " AND training_organizer_option_id in ($org_allowed_ids) ";
			}
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			$where .= $site_orgs ? " AND training_organizer_option_id in ($site_orgs) " : "";

			if ( $criteria['training_organizer_id'][0] )
				$where .= " AND training_organizer_option_id in (" . implode(',', $criteria['training_organizer_id']) . ")";
			if ( $criteria['training_title_id'][0] )
				$where .= " AND training_title_option_id in (" . implode(',', $criteria['training_title_id']) . ")";
			if (intval ( $criteria ['start-year'] )) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
				$where .= ' training_start_date >= \'' . $startDate . '\' ';
			}

			if (intval ( $criteria ['end-year'] )) {
				if (strlen ( $where ))
				$where .= ' AND ';
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where .= ' training_start_date <= \'' . $endDate . '\'  ';
			}

			$sql .= $where;
			$rowArray = $db->fetchAll ( $sql );
			if ($rowArray){

			// now we have trainings, lets get participants trainers and evaluations
			require_once ('models/table/Training.php');
			require_once ('models/table/TrainingToTrainer.php');
			require_once ('models/table/PersonToTraining.php');
			require_once ('models/table/Evaluation.php');

			$tableObj = new Training ( );

			$output = array();
			$text = "<html><body>";//TA:#526
			$text .= '<a href="'. str_replace('?','/outputType/excel/?',$_SERVER['REQUEST_URI']) .'">' . t('Export to').' MS Excel</a>&nbsp;<img src="'.(Settings::$COUNTRY_BASE_URL).'/images/excel.jpg" /></a>';    

			$locations = Location::getAll();
			$answers = $db->fetchAll( 'SELECT * FROM evaluation_question_response' );
			$responselist = $db->fetchAll( 'SELECT *,evaluation_response.id as evaluation_response_id FROM evaluation_response
											LEFT JOIN evaluation_to_training ett ON ett.id = evaluation_response.evaluation_to_training_id ORDER BY training_id ASC');
			// response list is basically a hash of training_id, evaluation_to_training_id, evaluation_id, evaluation_response.id, and trainer_person_id, cool!
			$questionz = array();
			
			//TA:#526
			require_once('Output/PHPExcel/IOFactory.php');
			$objPHPExcel = new PHPExcel();
			$ActiveSheet = $objPHPExcel->setActiveSheetIndex(0);
			$rowIndex = 1;
			//

			// loop through trainings
			foreach ( $rowArray as $row ) {
				if (!isset($row['id']) || empty($row['id']))
					continue;

				$output_row = array();//TA:#526
				$rowRay = @$tableObj->getTrainingInfo ( $row ['id'] );

				// calculate end date
				switch ($rowRay ['training_length_interval']) {
					case 'week' :
					$days = $rowRay ['training_length_value'] * 7;
					break;
					case 'day' :
					$days = $rowRay ['training_length_value'] - 1; // start day counts as a day?
					break;
					default :
					$days = false;
					break;
				}

				if ($days) {
					$rowRay ['training_end_date'] = strtotime ( "+$days day", strtotime ( $rowRay ['training_start_date'] ) );
					$rowRay ['training_end_date'] = date ( 'Y-m-d', $rowRay ['training_end_date'] );
				} else {
					$rowRay ['training_end_date'] = $rowRay ['training_start_date'];
				}

				$rowRay ['duration'] = $rowRay ['training_length_value'] . ' ' . $rowRay ['training_length_interval'] . (($rowRay ['training_length_value'] == 1) ? "" : "s");

				if (! $rowRay['training_title']) $rowRay['training_title'] = t('Unknown');

			
				$text .= "
				<p>
				<strong>" . t ('Training').' '.t('ID' ) . ":</strong> {$rowRay['id']}<br />
				<strong>" . t ('Training').' '.t('Name' ) . ":</strong> {$rowRay['training_title']}<br />
				<strong>" . t ('Training Center' ) . ":</strong> {$rowRay['training_location_name']}<br />
				<strong>" . t ('Dates') . ":</strong> {$rowRay['training_start_date']}" . ($rowRay ['training_start_date'] != $rowRay ['training_end_date'] ? ' - ' . $rowRay ['training_end_date'] : '') . "<br />
				<strong>" . t ('Course Length') . ":</strong> {$rowRay['duration']}<br />
				<strong>" . t ('Training').' '.t('Topic' ) . ":</strong> {$rowRay['training_topic_phrase']}<br />
				<strong>" . t ('Training').' '.t('Level' ) . ":</strong> {$rowRay['training_level_phrase']}<br />
				" . ($rowRay ['training_got_curriculum_phrase'] ? "<strong>" . $this->tr ( 'GOT Curriculum' ) . "</strong>: {$rowRay['training_got_curriculum_phrase']}<br />" : '') . "
				" . ($rowRay ['got_comments'] ? "<strong>" . $this->tr ( 'GOT Comment' ) . "</strong>: {$rowRay['got_comments']}<br />" : '') . "
				" . ($rowRay ['comments'] ? "<strong>" . $this->tr ( 'Comments' ) . "</strong>: {$rowRay['comments']}<br />" : "") . "
				" . ($rowRay ['pepfar'] ? "<strong>" . $this->tr ( 'PEPFAR Category' ) . ":</strong> {$rowRay['pepfar']}<br />" : "") . "
				" . ($rowRay ['objectives'] ? "<strong>" . $this->tr ( 'Course Objectives' ) . ":</strong> " . nl2br ( $rowRay ['objectives'] ) : '') . "
				</p>
				";
				
				//TA:#526
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Training').' '.t('ID' ) . ": " . $rowRay['id']);
				$ActiveSheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);
				$rowIndex++;
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Training').' '.t('Name' ) . ": " . $rowRay['training_title']);
				$rowIndex++;
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Training Center').' ' . ": " . $rowRay['training_location_name']);
				$rowIndex++;
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Dates').' ' . ": " . $rowRay['training_start_date'] . 
				    ($rowRay ['training_start_date'] != $rowRay ['training_end_date'] ? ' - ' . $rowRay ['training_end_date'] : ''));
				$rowIndex++;
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Course Length').' ' . ": " . $rowRay['duration']);
				$rowIndex++;
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Training').' '.t('Topic' ) . ": " . $rowRay['training_topic_phrase']);
				$rowIndex++;
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Training').' '.t('Level' ) . ": " . $rowRay['training_level_phrase']);
				$rowIndex++;
				if($rowRay ['comments']){
				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Comments').' ' . ": " . $rowRay['comments']);
				    $rowIndex++;
				}
				if($rowRay ['pepfar']){
				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('PEPFAR Category').' ' . ": " . $rowRay['pepfar']);
				    $rowIndex++;
				}
				if($rowRay ['objectives']){
				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Course Objectives').' ' . ": " . $rowRay['objectives']);
				    $rowIndex++;
				}
				///
				
				// evaluations
				$question_lookup = array(); // questions needed by attached evaluations

				foreach ($responselist as $responserow) // loop through completed evaluations (responses)
				{

					if ( $responserow['training_id'] > $row['id'])
						break; // speed up, its sorted
					if ( $responserow['training_id'] != $row['id'] )
						continue;

					// found a valid training/repsonse combo, lets attach the answers and questions to the training results for EZness
					if (!isset($row['questions']))
						$row['questions'] = array();
					// get ans
					foreach ($answers as $key => $value) {
						if ($value['evaluation_response_id'] == $responserow['evaluation_response_id']){
							if (!isset($row['answers']))
								$row['answers'][$responserow['evaluation_id']][$responserow['evaluation_response_id']]= array('');
							// training['answers'][response_id][question_id] => answer
							$row['answers'][$responserow['evaluation_id']][$responserow['evaluation_response_id']][$value['evaluation_question_id']] = $value['value_text'] ? $value['value_text'] : $value['value_int'];
						}
					}
					// get q
					$question_lookup[] = $responserow['evaluation_id'];
				}

				// get all questions (usually a larger table than responses)
				foreach (array_unique($question_lookup) as $eval_id) {
					if (!trim($eval_id))
						continue;
					if(! isset($questionz[$eval_id]) ) // fetch once
					$questionz[$eval_id] = @Evaluation::fetchAllQuestions($eval_id)->toArray();
				}
				// evals now in rowRay['answers'], questions in $questionz
				//end evaluations

				/* Trainers */
				$trainers = @TrainingToTrainer::getTrainers ( $row ['id'] )->toArray();
				if ($trainers){
				    $text .= '
				<table border="1" style="border-collapse:collapse;" cellpadding="3">
					<caption style="text-align:left;"><em>' . t ('Course').' '.t('Trainers') . '</em></caption>
				<tr>
				<th>' . $this->tr ( 'Last Name' ) . '</th>
				<th>' . $this->tr ( 'First Name' ) . '</th>
				<th>' . t ( 'Days' ) . '</th>
				</tr>
				';
				foreach ( $trainers as $tRow ) {
				    $text .= "
					<tr>
					<td>{$tRow['last_name']}</td>
					<td>{$tRow['first_name']}</td>
					<td>{$tRow['duration_days']}</td>
					</tr>
					";
				}
				$text .= '</table><br>';
				
				    //TA:#526
				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Course').' '.t('Trainers'));
				    $ActiveSheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);
				    $ActiveSheet->getStyle('A' . $rowIndex)->getFont()->setItalic(true);
				    $rowIndex++;
				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, $this->tr ( 'Last Name' ));
				    $ActiveSheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);
				    $ActiveSheet->getStyle('A' . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(1) . $rowIndex, $this->tr ( 'First Name' ));
				    $ActiveSheet->getStyle('B' . $rowIndex)->getFont()->setBold(true);
				    $ActiveSheet->getStyle('B' . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(2) . $rowIndex, t ( 'Days' ));
				    $ActiveSheet->getStyle('C' . $rowIndex)->getFont()->setBold(true);
				    $ActiveSheet->getStyle('C' . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				    $rowIndex++;
				    foreach ( $trainers as $tRow ) {
				        $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, $tRow['last_name']);
				        $ActiveSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				        $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(1) . $rowIndex, $tRow['first_name']);
				        $ActiveSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex(1) . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				        $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(2) . $rowIndex, $tRow['duration_days']);
				        $ActiveSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex(2) . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
				        $rowIndex++;
				    }
				    ///
				}
				
				$persons = @PersonToTraining::getParticipants ( $row ['id'] )->toArray ();

				$text .= '
				<table border="1" style="border-collapse:collapse;" cellpadding="3">
				<caption style="text-align:left;"><em>' . t ( 'Course Participants' ) . '</em></caption>
				<tr>';
				
				//TA:#526
				$ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ( 'Course Participants' ));
				$ActiveSheet->getStyle('A' . $rowIndex)->getFont()->setBold(true);
				$ActiveSheet->getStyle('A' . $rowIndex)->getFont()->setItalic(true);
				$rowIndex++;
				///
				
				$headers = array();
				$headers []= $this->tr ( 'Last Name' );
				if ( $this->setting ( 'display_middle_name') && !$this->setting('display_middle_name_last')) $headers []= $this->tr ( 'Middle Name' ); //TA:#544
				$headers []= $this->tr ( 'First Name' );
				if ( $this->setting ( 'display_middle_name') && $this->setting('display_middle_name_last')) $headers []= $this->tr ( 'Middle Name' ); //TA:#544
				$headers []= t ( 'Date of Birth' );
				$headers []= $this->tr ( 'Facility' );
				if ( $add_additional ) {
					$headers []= $this->tr ( 'Region A (Province)' );
					if  ($this->setting ( 'display_region_b' )) $headers []= $this->tr ( 'Region B (Health District)' );
					if  ($this->setting ( 'display_region_c' )) $headers []= $this->tr ( 'Region C (Local Region)' );
					if  ($this->setting ( 'display_region_d' )) $headers []= $this->tr ( 'Region D' );
					if  ($this->setting ( 'display_region_e' )) $headers []= $this->tr ( 'Region E' );
					if  ($this->setting ( 'display_region_f' )) $headers []= $this->tr ( 'Region F' );
					if  ($this->setting ( 'display_region_g' )) $headers []= $this->tr ( 'Region G' );
					if  ($this->setting ( 'display_region_h' )) $headers []= $this->tr ( 'Region H' );
					if  ($this->setting ( 'display_region_i' )) $headers []= $this->tr ( 'Region I' );
					$headers []= t ( 'Primary Qualification' );
					$headers []= t ( 'Secondary Qualification' );
				}
				if ( $this->setting ( 'module_attendance_enabled' ) ) {
					$headers []= t ( 'Days Attended' );
					$headers []= t ( 'Complete' );
				}
				if ( $is_extended ) {
					$headers []= t ( 'Pre-Test' );
					$headers []= t ( 'Post-Test' );
					$headers []= t ( 'Change in Score' );
				}

				/* Participants */
				// map each score-other to a hash
				$scores = array(); $scoreOtherHeaders = array();
				foreach ( $persons as $r ) {
					if (!$r['person_id'])
						continue;
					$keys = explode(',', $r['score_other_k']);
					$values = explode(',', $r['score_other_v']);
					foreach ( $keys as $i=>$k ) {
						$k = trim($k);
						if ($k) {
							$scores[$r['person_id']][$k] = $values[$i] ;
							$scoreOtherHeaders[] = $k;
						}
					}
				}

				$scoreOtherHeaders = array_unique($scoreOtherHeaders);
				foreach ($scoreOtherHeaders as $h)
					$headers [] = $h;

					$text .= '<th>'.implode('</th><th>', $headers);
					$text .= '</th></tr>';
					
					//TA:#526
					$colIndex=0;
					foreach ($headers as $h){
					    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($colIndex) . $rowIndex, $h);
					    $ActiveSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex($colIndex) . $rowIndex)->getFont()->setBold(true);
					    $ActiveSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex($colIndex) . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
					    $colIndex++;
			        }
					$rowIndex++;
					///

				/* Participants */
				foreach ( $persons as $r ) {
					if (is_numeric ( $r ['score_percent_change'] )) { // add percent
						if ($r ['score_percent_change'] > 0) {
							$r ['score_percent_change'] = "+" . $r ['score_percent_change'];
						}
						$r ['score_percent_change'] = "{$r['score_percent_change']}%";
					}
					$r ['score_change'] = '';

					if ($r ['score_post']) {
						$r ['score_change'] = $r ['score_post'] - $r ['score_pre'];
					}

					$text .= "<tr><td>";
					$body_fields = array();
					$body_fields[] = $r['last_name'];
					if ( $this->setting ( 'display_middle_name') && !$this->setting('display_middle_name_last')) $body_fields[] = $r['middle_name'];//TA:#544
					$body_fields[] = $r['first_name'];
					if ( $this->setting ( 'display_middle_name') && $this->setting('display_middle_name_last')) $body_fields[] = $r['middle_name'];//TA:#544
					$body_fields[] = $r['birthdate'];
					$body_fields[] = $r['facility_name'];
					if ( $add_additional ) {
						$region_ids = Location::getCityInfo($r['location_id'], $this->setting('num_location_tiers'), $locations);
						$region_ids = Location::cityInfotoHash($region_ids);
						$body_fields[] = $locations[$region_ids['province_id']]['name'];
						if ( $this->setting ( 'display_region_b' ) ) $body_fields[] = $locations[$region_ids['district_id']]['name'];
						if ( $this->setting ( 'display_region_c' ) ) $body_fields[] = $locations[$region_ids['region_c_id']]['name'];
						if ( $this->setting ( 'display_region_d' ) ) $body_fields[] = $locations[$region_ids['region_d_id']]['name'];
						if ( $this->setting ( 'display_region_e' ) ) $body_fields[] = $locations[$region_ids['region_e_id']]['name'];
						if ( $this->setting ( 'display_region_f' ) ) $body_fields[] = $locations[$region_ids['region_f_id']]['name'];
						if ( $this->setting ( 'display_region_g' ) ) $body_fields[] = $locations[$region_ids['region_g_id']]['name'];
						if ( $this->setting ( 'display_region_h' ) ) $body_fields[] = $locations[$region_ids['region_h_id']]['name'];
						if ( $this->setting ( 'display_region_i' ) ) $body_fields[] = $locations[$region_ids['region_i_id']]['name'];

						if ( (!$r['primary_qualification']) OR ($r['primary_qualification'] == 'unknown')) {
							$body_fields[] = $r['qualification'];
							$body_fields[] = '';
						} else {
							$body_fields[] = $r['primary_qualification'];
							$body_fields[] = $r['qualification'];
						}
						//        $body_fields[] = $r['primary_responsibility'];
						//        $body_fields[] = $r['secondary_responsibility'];
					}
					if ( $this->setting ( 'module_attendance_enabled' ) ) {
						$body_fields[] = $r['duration_days'];
						$body_fields[] = $r['award_id'] ? $r['award_id'] : '';
					}
					if ( $is_extended ) {
						$body_fields[] = $r['score_pre'];
						$body_fields[] = $r['score_post'];
						$body_fields[] = $r['score_change'];
						//custom scores
						$pid = $r['person_id'];
						foreach($scoreOtherHeaders as $h) {
							$body_fields[] = ($scores[$pid][$h]) ? $scores[$pid][$h] : '&nbsp;'; // TODO should show a '&nbsp;' on empty not space, TrainSMART standard
						}

					}

					$text .= implode('</td><td>', $body_fields);
					$text .= "</td></tr>";
					
					//TA:#526
					$colIndex=0;
					foreach ($body_fields as $body_field){
					    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex($colIndex) . $rowIndex, $body_field);
					    $ActiveSheet->getStyle(PHPExcel_Cell::stringFromColumnIndex($colIndex) . $rowIndex)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
					    $colIndex++;
					}
					$rowIndex++;
					///
				}

				$text .= '</table><br>';

				// evaluations
				if ($row['answers'])
				{
				    $text .= '
						<table border="1" style="border-collapse:collapse;" cellpadding="3">
						<caption style="text-align:left;"><em>' . t ('Evaluations') . '</em></caption>';
				    
				    //TA:#526
// 				    $ActiveSheet->setCellValue(PHPExcel_Cell::stringFromColumnIndex(0) . $rowIndex, t ('Evaluations'));
// 				    $rowIndex++;
				    ///
				    
					$qnames = array();
					$answer = array();
					$qids   = array();
					$last_eval_id = 0;
					foreach( $row['answers'] as $eval_id => $evalresponse ) {
						$hdr_txt = "";
						if ( $eval_id != $last_eval_id ){ // print header row
							foreach ($questionz as $evaluationid => $qArray) {
								if ($eval_id != $evaluationid)
									continue;
								foreach ($qArray as $q){
									$ex = '';
									$qids[] = $q['id'];
									if ($q['question_type'] == 'Likert3' || $q['question_type'] == 'Likert3NA') $ex = "&nbsp;(1-3)";
									if ($q['question_type'] == 'Likert' || $q['question_type'] == 'LikertNA') $ex = "&nbsp;(1-5)";
									$hdr_txt .= '<th>'.$q['question_text'].$ex.'</th>';
								}
								break;
							}
							if($hdr_txt)
							    $text .= "<tr>$hdr_txt</tr>";
						}

						foreach ( $evalresponse as $reponseid => $answerrow ) {
							// attempt has build evalation question list
							if(isset($answerrow[0]) && !$answerrow[0])
								unset($answerrow[0]); // bugfix, one of my array() inits is wrong. TODO

							// pad results (missing answers wont line up in html table otherwise)
							foreach ($qids as $qid) {
								if(! isset($answerrow[$qid]))
									$answerrow[$qid] = '&nbsp;'; // TODO should show a '-', TrainSMART standard
							}
							ksort($answerrow); // due to filling in missing answers above, need to resort here

							$text .= '<tr><td>'.implode('</td><td>', $answerrow).'</td></tr>';
						}
					}
					$text .= '</table><br>';

			}
				// done
			$text .= '<br><hr size="1">';
			
			//TA:#526
			$rowIndex++;
				
			}

			$text .= '</html></body>';
			
			//TA:#526
			if ($this->getParam('outputType') === 'excel') {
			    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			    $isIE = strstr( $_SERVER['HTTP_USER_AGENT'], 'MSIE' );
			    if ( $isIE ) {
			        header('Content-Disposition: inline; filename="roster.xlsx"');
			        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			        header('Pragma: "public"');
			    }else{
			        header('Content-Disposition: attachment;filename="roster.xlsx"');
			        header('Pragma: no-cache');
			    }
			    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			    $objWriter->save('php://output');
			}else{
			    echo $text; 
			}
			///

// 			foreach ($output as $key => $value) {
// 			    $text .= utf8_decode($value);
// 			    $text .= PHP_EOL;
// 			}

			exit ();
			}else{
			    echo '<script>alert("No trainings found.");</script>';
			}

			} catch (Exception $e) {
				echo $e->getMessage() . '<br>' . PHP_EOL;
				die();
			}
		}

		//form drop downs
		$organizersArray = OptionList::suggestionList ( 'training_organizer_option', 'training_organizer_phrase', false, false, false );
		$this->viewAssignEscaped ( 'organizers', $organizersArray );
		$titlesArray = OptionList::suggestionList ( 'training_title_option', 'training_title_phrase', false, false, false );
		$this->viewAssignEscaped ( 'titles', $titlesArray );
		$this->view->assign ( 'criteria', $criteria );


	}

	public function evaluationsAction() {
		$criteria = $this->getAllParams();
		$db = $this->dbfunc();
		$status = ValidationContainer::instance ();
		$criteria['evaluation_id'] =  array_filter($this->_array_me($criteria['evaluation_id'])); // filter out empty items, force to an array
		$eval_ids = implode(',', $criteria['evaluation_id']);

		//find the first date in the database
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = "SELECT MIN(timestamp_created) as \"start\" FROM evaluation_response WHERE is_deleted = 0";
		$rowArray = $db->fetchAll ( $sql );
		$start_default = $rowArray [0] ['start'];
		$parts = explode(' ', $start_default );
		$parts = explode('-', $parts [0] );
		$criteria ['start-year'] = $parts [0];
		$criteria ['start-month'] = $parts [1];
		$criteria ['start-day'] = $parts [2];

		if ($this->getSanParam ( 'start-year' ))
		$criteria ['start-year'] = $this->getSanParam ( 'start-year' );
		if ($this->getSanParam ( 'start-month' ))
		$criteria ['start-month'] = $this->getSanParam ( 'start-month' );
		if ($this->getSanParam ( 'start-day' ))
		$criteria ['start-day'] = $this->getSanParam ( 'start-day' );
		if ($this->view->mode == 'search') {
			$sql = "SELECT MAX(timestamp_created) as \"start\" FROM evaluation_response ";
			$rowArray = $db->fetchAll ( $sql );
			$end_default = $rowArray [0] ['start'];
			$parts = explode(' ', $start_default );
			$parts = explode('-', $parts [0] );
			$criteria ['end-year'] = $parts [0];
			$criteria ['end-month'] = $parts [1];
			$criteria ['end-day'] = $parts [2];
		} else {
			$criteria ['end-year'] = date ( 'Y' );
			$criteria ['end-month'] = date ( 'm' );
			$criteria ['end-day'] = date ( 'd' );
		}

		if ($criteria['go']) // run report
		{

			list($a, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
			list($locationFlds, $locationsubquery) = Location::subquery($this->setting('num_location_tiers'), $location_tier, $location_id, true);
			
			$helper = new Helper();
			
			//TA:79 make filtering by orginizer
			$user_orginizer_sql = " join training_organizer_option on training_organizer_option.id=training.training_organizer_option_id
join user_to_organizer_access on user_to_organizer_access.training_organizer_option_id = training.training_organizer_option_id and user_to_organizer_access.user_id=" . $helper->myid();
			
			if ( array_search('training_organizer_option_all',User::getACLs ( $helper->myid() )) !== false ){
			    $user_orginizer_sql = "";
			}

			$sql = " SELECT
						tl.training_location_name,
						evaluation.title,
						eqr.id,
						evaluation_response_id,
						evaluation_question_id,"
						.//IFNULL(a.answer_phrase, IFNULL(value_text, value_int)) as answer,
						implode(',',$locationFlds).",
						evaluation_to_training_id,
						trainer_person_id,
						evaluation.id as evaluation_id,
						training_id,
						training_title_phrase,
						title,
						question_text,
						question_type,
						weight,
						CONCAT(p.first_name, CONCAT(' ', p.last_name)) as person_full_name,
						CONCAT(t.first_name, CONCAT(' ', t.last_name)) as trainer_full_name,
						GROUP_CONCAT(IFNULL(value_text, value_int)) as score_array,
						GROUP_CONCAT(value_int) as scores_int
					 FROM
						evaluation_question_response eqr
						LEFT JOIN evaluation_response er      ON  eqr.evaluation_response_id = er.id
						INNER JOIN evaluation_to_training ett ON  ett.id = er.evaluation_to_training_id AND ett.training_id IS NOT NULL
						INNER JOIN training                   ON  training.id = ett.training_id AND training.is_deleted = 0 " .
						//TA:79 make filtering by orginizer 
						    $user_orginizer_sql .
						    
						" LEFT JOIN training_location tl        ON  tl.id = training.training_location_id
						LEFT JOIN training_title_option tto   ON  training.training_title_option_id = tto.id
						LEFT JOIN evaluation                  ON  evaluation.id = ett.evaluation_id
						LEFT JOIN evaluation_question eq      ON  eq.id = eqr.evaluation_question_id
						"//LEFT JOIN evaluation_custom_answers a ON  a.question_id = eq.id
						."
						LEFT JOIN person p                    ON  p.id = er.person_id
						LEFT JOIN person t                    ON  t.id = er.trainer_person_id
						LEFT JOIN ($locationsubquery) as l ON tl.location_id = l.id ";

			$where [] = 'evaluation.is_deleted = 0 AND er.is_deleted = 0 AND eq.is_deleted = 0 AND eqr.is_deleted = 0';
			if ($criteria['evaluation_id'])              $where [] = "ett.evaluation_id in ($eval_ids)";
			if ($criteria['training_id'])                $where [] = 'training_id = ' . $criteria['training_id'];
			if ($criteria['training_title_option_id'])   $where [] = 'training.training_title_option_id = ' . $criteria['training_title_option_id'];
			if ($criteria['person_id'])                  $where [] = "(p.id = {$criteria['person_id']} or t.id = {$criteria['person_id']})";
			if ( $criteria ['start-year'] && !$training_id ) { // bugfix: !training_id todo: these do not play well with evaluations by training_id (probably because of my test db-not sure)
					$startDate = $criteria ['start-year'] . '-' . $criteria ['start-month'] . '-' . $criteria ['start-day'];
					$where[] .= ' er.timestamp_created >= \'' . $startDate . '\' ';
				}

			if ( $criteria ['end-year']  && !$training_id ) {
				$endDate = $criteria ['end-year'] . '-' . $criteria ['end-month'] . '-' . $criteria ['end-day'];
				$where[] = ' er.timestamp_created <= \'' . $endDate . ' 23:59\'  ';
			}
			if ($locWhere = $this->getLocationCriteriaWhereClause($criteria)) {
				$where [] = $locWhere;
			}

			$sql .= ' WHERE ' . implode(' AND ', $where);

			$sql .= " GROUP BY eq.id,evaluation.id,training_id,person_full_name, trainer_full_name";
			$sql .= " ORDER BY ett.training_id, ett.evaluation_id, er.timestamp_created, weight";
			
			$rows = $db->fetchAll($sql);
			if ($rows) {

				// pivot rows to columns, based on the # of times a participant has a linked evaluation
				$maxVisits = 0;
				foreach ($rows as $i => $row) {  // count # of visits, keep the max # visits
					$rows[$i]['parsed_scores'] = explode(',', $row['score_array']);  // explode list of scores
					$cnt = count($rows[$i]['parsed_scores']);
					if ($cnt > $maxVisits)
						$maxVisits = $cnt;
				}

				foreach ($rows as $i => $row) {
					$rows[$i]['question_number'] = $row['weight'] + 1;
					for ($k = 0; $k < $maxVisits; $k++) { // pivot rows to columns
						$rows[$i]['response'.($k+1)] = isset($row['parsed_scores'][$k]) ? $row['parsed_scores'][$k] : ''; // do it here so we can export to excel
					}
					$avgsArray = explode(',', $row['scores_int']); // averages, value_int column only
					$rows[$i]['average'] = ((isset($avgsArray[0]) && trim($avgsArray[0]) !== '') ? number_format( array_sum($avgsArray) / count($avgsArray), 2) : '-'); // if it seems to not be empty we can do some calculations
					// cleanup - in case of export
					unset($rows[$i]['score_array']);
					unset($rows[$i]['scores_int']);
					unset($rows[$i]['parsed_scores']);
					unset($rows[$i]['id']);
					unset($rows[$i]['evaluation_response_id']);
					unset($rows[$i]['evaluation_question_id']);
					unset($rows[$i]['answer']);
					unset($rows[$i]['evaluation_to_training_id']);
					unset($rows[$i]['trainer_person_id']);
					unset($rows[$i]['evaluation_id']);
					unset($rows[$i]['weight']);
					unset($rows[$i]['province_id']);
					unset($rows[$i]['district_id']);
					unset($rows[$i]['region_c_id']);
					unset($rows[$i]['region_d_id']);
					unset($rows[$i]['region_e_id']);
					unset($rows[$i]['region_f_id']);
					unset($rows[$i]['region_g_id']);
					unset($rows[$i]['region_h_id']);
					unset($rows[$i]['region_i_id']);
				}

				$this->viewAssignEscaped('numColumns', $maxVisits);
				$this->viewAssignEscaped('results', $rows);

				if ($this->getParam ( 'outputType' ))
					$this->sendData ( $this->reportHeaders ( false, $rows ) );

			} else {
				$status->setStatusMessage( 'Error running report. There might be no data.' );
			}
		}
		$this->viewAssignEscaped ('pageTitle', t('Evaluation Report'));
		$this->viewAssignEscaped ('locations', Location::getAll());
		$this->viewAssignEscaped ( 'evaluations', OptionList::suggestionList ( 'evaluation', 'title', false, false, false ) );
		$this->view->assign ( 'criteria', $criteria );
		//people
		require_once('models/table/Person.php');
		if ( ! $criteria['go'] ) { // no report, redirecting, we can skip this expensive call
			$peopleArray = Person::suggestionList(false, 1999, $this->setting('display_middle_name_last'));
			$this->viewAssignEscaped('people', $peopleArray);
		}
		//titles
		require_once('views/helpers/DropDown.php');
		$this->view->assign ( 'titles',   DropDown::generateHtml ( 'training_title_option', 'training_title_phrase', $criteria['training_title_option_id'], false, $this->view->viewonly, false ) );
	}

	public function evaluationsReportAction()
	{
		require_once('models/table/Trainer.php');
		require_once('models/table/TrainingLocation.php');

		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		//criteria
		$criteria['showTrainer']           = $this->getSanParam( 'showTrainer' );
		$criteria['showCategory']          = $this->getSanParam( 'showCategory' );
		$criteria['showTitle']             = $this->getSanParam( 'showTitle' );
		$criteria['showLocation']          = $this->getSanParam( 'showLocation' );
		$criteria['showOrganizer']         = $this->getSanParam( 'showOrganizer' );
		$criteria['showMechanism']         = $this->getSanParam( 'showMechanism' );
		$criteria['showTopic']             = $this->getSanParam( 'showTopic' );
		$criteria['showLevel']             = $this->getSanParam( 'showLevel' );
		$criteria['showPepfar']            = $this->getSanParam( 'showPepfar' );
		$criteria['showMethod']            = $this->getSanParam( 'showMethod' );
		$criteria['showFunding']           = $this->getSanParam( 'showFunding' );
		$criteria['showTOT']               = $this->getSanParam( 'showTOT' );
		$criteria['showRefresher']         = $this->getSanParam( 'showRefresher' );
		$criteria['showGotCurric']         = $this->getSanParam( 'showGotCurric' );
		$criteria['showGotComment']        = $this->getSanParam( 'showGotComment' );
		$criteria['showLang1']             = $this->getSanParam( 'showLang1' );
		$criteria['showLang2']             = $this->getSanParam( 'showLang2' );
		$criteria['showCustom1']           = $this->getSanParam( 'showCustom1' );
		$criteria['showCustom2']           = $this->getSanParam( 'showCustom2' );
		$criteria['showCustom3']           = $this->getSanParam( 'showCustom3' );
		$criteria['showCustom4']           = $this->getSanParam( 'showCustom4' );
		$criteria['showCustom5']           = $this->getSanParam( 'showCustom5' );
		$criteria['showProvince']          = $this->getSanParam( 'showProvince' );
		$criteria['showDistrict']          = $this->getSanParam( 'showDistrict' );
		$criteria['showRegionC']           = $this->getSanParam( 'showRegionC' );
		$criteria['showRegionD']           = $this->getSanParam( 'showRegionD' );
		$criteria['showRegionE']           = $this->getSanParam( 'showRegionE' );
		$criteria['showRegionF']           = $this->getSanParam( 'showRegionF' );
		$criteria['showRegionG']           = $this->getSanParam( 'showRegionG' );
		$criteria['showRegionH']           = $this->getSanParam( 'showRegionH' );
		$criteria['showRegionI']           = $this->getSanParam( 'showRegionI' );
		$criteria['evaluation_id']         = $this->getSanParam( 'evaluation_id' );
		$criteria['trainer_id']            = $this->getSanParam( 'trainer_id' );
		$criteria['training_category_id']  = $this->getSanParam( 'training_category_id' );
		$criteria['training_title_id']     = $this->getSanParam( 'training_title_id' );
		$criteria['training_location_id']  = $this->getSanParam( 'training_location_id' );
		$criteria['training_organizer_id'] = $this->getSanParam( 'training_organizer_id' );
		$criteria['training_mechanism_id'] = $this->getSanParam( 'training_mechanism_id' );
		$criteria['training_topic_id']     = $this->getSanParam( 'training_topic_id' );
		$criteria['training_level_id']     = $this->getSanParam( 'training_level_id' );
		$criteria['training_pepfar_id']    = $this->getSanParam( 'training_pepfar_id' );
		$criteria['training_method_id']    = $this->getSanParam( 'training_method_id' );
		$criteria['training_funding_id']   = $this->getSanParam( 'training_funding_id' );
		$criteria['training_tot_id']       = $this->getSanParam( 'training_tot_id' );
		$criteria['training_refresher_id'] = $this->getSanParam( 'training_refresher_id' );
		$criteria['training_got_id']       = $this->getSanParam( 'training_got_id' );
		$criteria['training_gotcomment_id']= $this->getSanParam( 'training_gotcomment_id' );
		$criteria['training_lang1_id']     = $this->getSanParam( 'training_lang1_id' );
		$criteria['training_lang2_id']     = $this->getSanParam( 'training_lang2_id' );
		$criteria['training_custom1_id']   = $this->getSanParam( 'training_custom1_id' );
		$criteria['training_custom2_id']   = $this->getSanParam( 'training_custom2_id' );
		$criteria['training_custom3_id']   = $this->getSanParam( 'training_custom3_id' );
		$criteria['training_custom4_id']   = $this->getSanParam( 'training_custom4_id' );
		$criteria['province_id']           = $this->getSanParam( 'province_id' );
		$criteria['district_id']           = $this->getSanParam( 'district_id' );
		$criteria['region_c_id']           = $this->getSanParam( 'region_c_id' );
		$criteria['region_d_id']           = $this->getSanParam( 'region_d_id' );
		$criteria['region_e_id']           = $this->getSanParam( 'region_e_id' );
		$criteria['region_f_id']           = $this->getSanParam( 'region_f_id' );
		$criteria['region_g_id']           = $this->getSanParam( 'region_g_id' );
		$criteria['region_h_id']           = $this->getSanParam( 'region_h_id' );
		$criteria['region_i_id']           = $this->getSanParam( 'region_i_id' );
		$criteria['startdate']             = $this->getSanParam( 'startdate' );
		$criteria['enddate']               = $this->getSanParam( 'enddate' );
		$criteria['has_response']          = $this->getSanParam( 'has_response' );
		$criteria ['limit'] = $this->getSanParam ( 'limit' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		if($criteria['go'])
		{
			// fields
			$sql = 'SELECT pt.id as "id", ptc.pcnt, pt.training_start_date, pt.training_end_date, pt.has_known_participants  '; // training fields
			$sql .= ',title, trainer_person_id, first_name, last_name, question_text, question_type, weight, value_text, value_int'; // evaluation fields
			if ( $criteria ['showRegionI'] ) {   $sql .= ', pt.region_i_name ';	}
			if ( $criteria ['showRegionH'] ) {   $sql .= ', pt.region_h_name '; }
			if ( $criteria ['showRegionG'] ) {   $sql .= ', pt.region_g_name '; }
			if ( $criteria ['showRegionF'] ) {   $sql .= ', pt.region_f_name '; }
			if ( $criteria ['showRegionE'] ) {   $sql .= ', pt.region_e_name '; }
			if ( $criteria ['showRegionD'] ) {   $sql .= ', pt.region_d_name '; }
			if ( $criteria ['showRegionC'] ) {   $sql .= ', pt.region_c_name '; }
			if ( $criteria ['showDistrict'] ) {  $sql .= ', pt.district_name '; }
			if ( $criteria ['showProvince'] ) {  $sql .= ', pt.province_name '; }
			if ( $criteria ['showLocation'] ) {  $sql .= ', pt.training_location_name '; }
			if ( $criteria ['showOrganizer'] ) { $sql .= ', torg.training_organizer_phrase as training_organizer_phrase ';	}
			if ( $criteria ['showMechanism']  && $this->setting('display_training_partner')) { $sql .= ', organizer_partners.mechanism_id ';	}
			if ( $criteria ['showLevel'] ) {     $sql .= ', tlev.training_level_phrase '; }
			if ( $criteria ['showCategory'] ) {  $sql .= ', tcat.training_category_phrase '; }
			if ( $criteria ['showTitle'] ) {     $sql .= ', training_title '; }
			if ( $criteria ['showPepfar']  || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0') { $sql .= ', GROUP_CONCAT(DISTINCT tpep.pepfar_category_phrase) as "pepfar_category_phrase" '; }
			if ( $criteria ['showMethod'] ) {	 $sql .= ', tmeth.training_method_phrase '; }
			if ( $criteria ['showTopic'] ) {     $sql .= ', GROUP_CONCAT(DISTINCT ttopic.training_topic_phrase ORDER BY training_topic_phrase) AS "training_topic_phrase" '; }
			if ( $criteria ['showTOT'] ) {       $sql .= ", IF(is_tot,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_tot"; }
			if ( $criteria ['showRefresher'] ) { $sql .= ", IF(is_refresher,'" . t ( 'Yes' ) . "','" . t ( 'No' ) . "') AS is_refresher"; }
			if ( $criteria ['showLang2'] ) {     $sql .= ', tlos.language_phrase as "secondary_language_phrase" '; }
			if ( $criteria ['showLang1'] ) {     $sql .= ', tlop.language_phrase as "primary_language_phrase" '; }
			if ( $criteria ['showGotComment'] ){ $sql .= ", pt.got_comments"; }
			if ( $criteria ['showGotCurric'] ) { $sql .= ', tgotc.training_got_curriculum_phrase '; }
			if ( $criteria ['showFunding'] ) {   $sql .= ', GROUP_CONCAT(DISTINCT tfund.funding_phrase ORDER BY funding_phrase) as "funding_phrase" '; }
			if ( $criteria ['showCustom1'] ) {   $sql .= ', tqc.custom1_phrase '; }
			if ( $criteria ['showCustom2'] ) {   $sql .= ', tqc.custom2_phrase '; }
			if ( $criteria ['showCustom3'] ) {   $sql .= ', pt.custom_3'; }
			if ( $criteria ['showCustom4'] ) {   $sql .= ', pt.custom_4'; }
			//if ( $criteria ['showCustom5'] ) {   $sql .= ', pt.custom_5'; }

			list($dontcare, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
			$num_location_tiers = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_location_tiers, $location_tier, $location_id, true);

			$sql .= ' FROM (SELECT training.*, tto.training_title_phrase AS training_title,training_location.training_location_name, '.implode(',',$field_name).
					'       FROM training  ' .
					'         LEFT JOIN training_title_option tto ON (`training`.training_title_option_id = tto.id) ' .
					'         LEFT JOIN training_location ON training.training_location_id = training_location.id ' .
					'         LEFT JOIN ('.$location_sub_query.') as l ON training_location.location_id = l.id ' .
					'  WHERE training.is_deleted=0) as pt ';

			$sql .= ' LEFT JOIN (SELECT COUNT(id) as "pcnt",training_id FROM person_to_training GROUP BY training_id) as ptc ON ptc.training_id = pt.id ';

			// joins

			if ($criteria['trainer_id'])
			$sql .= ' LEFT JOIN training_to_trainer as t2t ON (t2t.training_id = pt.id AND t2t.trainer_id = ' . $criteria['trainer_id'].')';

			if ($criteria ['showOrganizer'] or $criteria ['training_organizer_id'] || $criteria ['showMechanism']  || $criteria ['training_mechanism_id'])
			$sql .= ' JOIN training_organizer_option as torg ON torg.id = pt.training_organizer_option_id ';

			if ($criteria ['showMechanism'] || $criteria ['training_mechanism_id'] && @$this->setting('display_training_partner'))
			$sql .= ' LEFT JOIN organizer_partners ON organizer_partners.organizer_id = torg.id';

			if ($criteria ['showLevel'] || $criteria ['training_level_id'])
			$sql .= ' JOIN training_level_option as tlev ON tlev.id = pt.training_level_option_id ';

			if ($criteria ['showMethod'] || $criteria ['training_method_id'])
			$sql .= ' JOIN training_method_option as tmeth ON tmeth.id = pt.training_method_option_id ';

			if ($criteria ['showPepfar'] || $criteria ['training_pepfar_id'] || $criteria ['training_pepfar_id'] === '0')
			$sql .= '	LEFT JOIN (SELECT training_id, ttpco.training_pepfar_categories_option_id, pepfar_category_phrase FROM training_to_training_pepfar_categories_option as ttpco JOIN training_pepfar_categories_option as tpco ON ttpco.training_pepfar_categories_option_id = tpco.id) as tpep ON tpep.training_id = pt.id ';

			if ($criteria ['showTopic'] || $criteria ['training_topic_id'])
			$sql .= ' LEFT JOIN (SELECT training_id, ttto.training_topic_option_id, training_topic_phrase FROM training_to_training_topic_option as ttto JOIN training_topic_option as tto ON ttto.training_topic_option_id = tto.id) as ttopic ON ttopic.training_id = pt.id ';

			if ($criteria ['showLang1'] || $criteria ['training_lang1_id'])
			$sql .= ' LEFT JOIN trainer_language_option as tlop ON tlop.id = pt.training_primary_language_option_id ';

			if ($criteria ['showLang2'] || $criteria ['training_lang2_id'])
			$sql .= ' LEFT JOIN trainer_language_option as tlos ON tlos.id = pt.training_secondary_language_option_id ';

			if ($criteria ['showFunding'] || (intval ( $criteria ['funding_min'] ) or intval ( $criteria ['funding_max'] )))
			$sql .= ' LEFT JOIN (SELECT training_id, ttfo.training_funding_option_id, funding_phrase, ttfo.funding_amount FROM training_to_training_funding_option as ttfo JOIN training_funding_option as tfo ON ttfo.training_funding_option_id = tfo.id) as tfund ON tfund.training_id = pt.id ';

			if ($criteria ['showGotCurric'] || $criteria ['training_got_id'])
			$sql .= ' LEFT JOIN training_got_curriculum_option as tgotc ON tgotc.id = pt.training_got_curriculum_option_id';

			if ($criteria ['showCategory'] or ! empty ( $criteria ['training_category_id'] ))
			$sql .= 'LEFT JOIN training_category_option_to_training_title_option tcotto ON (tcotto.training_title_option_id = pt.training_title_option_id)
					 LEFT JOIN training_category_option tcat ON (tcotto.training_category_option_id = tcat.id)';

			if ( $criteria['showCustom1'] || $criteria ['training_custom1_id'] )
			$sql .= ' LEFT JOIN training_custom_1_option as tqc ON pt.training_custom_1_option_id = tqc.id  ';

			if ( $criteria['showCustom2'] || $criteria ['training_custom2_id'] )
			$sql .= ' LEFT JOIN training_custom_2_option as tqc2 ON pt.training_custom_2_option_id = tqc2.id  ';

			#if ( $criteria['showCustom3'] || $criteria ['custom_3_id'] )
			#todo$sql .= ' LEFT JOIN training_custom_3_option as custom_3 ON pt.training_custom_3_option_id = tqc3.id  ';

			#if ( $criteria['showCustom4'] || $criteria ['custom_4_id'] )
			#todo$sql .= ' LEFT JOIN training_custom_4_option as custom_4 ON pt.training_custom_4_option_id = tqc4.id  ';

			$sql .= ' RIGHT JOIN evaluation_to_training ON pt.id = evaluation_to_training.training_id
					  RIGHT JOIN evaluation 	        ON evaluation_id = evaluation.id
					  RIGHT JOIN evaluation_response    ON evaluation_to_training.id = evaluation_response.evaluation_to_training_id
					  RIGHT JOIN evaluation_question    ON evaluation.id = evaluation_question.evaluation_id
					  RIGHT JOIN evaluation_question_response ON evaluation_response.id = evaluation_question_response.evaluation_response_id AND evaluation_question.id = evaluation_question_response.evaluation_question_id
					  LEFT JOIN person ON trainer_person_id = person.id ';

			// where
			$where =  array( ' pt.is_deleted=0 ' );

			// restricted access?? only show trainings we have the ACL to view
			require_once('views/helpers/TrainingViewHelper.php');
			$org_allowed_ids = allowed_organizer_access($this);
			if ($org_allowed_ids) { // doesnt have acl 'training_organizer_option_all'
				$org_allowed_ids = implode(',', $org_allowed_ids);
				$where [] = " pt.training_organizer_option_id in ($org_allowed_ids) ";
			}
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			if ($site_orgs)
				$where [] = " training_organizer_option_id in ($site_orgs) ";

			if ($criteria ['training_participants_type']) {
				if ($criteria ['training_participants_type'] == 'has_known_participants') {
					$where [] = ' pt.has_known_participants = 1 ';
				}
				if ($criteria ['training_participants_type'] == 'has_unknown_participants') {
					$where [] = ' pt.has_known_participants = 0 ';
				}
			}

			if ($criteria ['evaluation_id'])
				$where [] = ' evaluation.id = '.$criteria['evaluation_id'] ;

			if ($criteria ['trainer_id'])
				$where [] = ' trainer_person_id = '.$criteria['trainer_id'] ;

			if ($criteria ['training_location_id'])
				$where [] = ' pt.training_location_id = \'' . $criteria ['training_location_id'] . '\'';

			if ($criteria ['training_title_id'] or $criteria ['training_title_id'] === '0')
				$where [] = ' pt.training_title_option_id = ' . $criteria ['training_title_id'];

			if ($criteria ['training_organizer_id'] or $criteria ['training_organizer_id'] === '0')
				$where [] = ' pt.training_organizer_option_id = \'' . $criteria ['training_organizer_id'] . '\'';

			if ($criteria ['training_mechanism_id'] or $criteria ['training_mechanism_id'] === '0' && $this->setting('display_training_partner'))
				$where [] = ' organizer_partners.mechanism_id = \'' . $criteria ['training_mechanism_id'] . '\'';

			if ($criteria ['training_topic_id'] or $criteria ['training_topic_id'] === '0')
				$where [] = ' ttopic.training_topic_option_id = \'' . $criteria ['training_topic_id'] . '\'';

			if ($criteria ['training_level_id'])
				$where [] = ' pt.training_level_option_id = \'' . $criteria ['training_level_id'] . '\'';

			if ($criteria ['training_pepfar_id'] or $criteria ['training_pepfar_id'] === '0')
				$where [] = ' tpep.training_pepfar_categories_option_id = \'' . $criteria ['training_pepfar_id'] . '\'';

			if ($criteria ['training_method_id'] or $criteria ['training_method_id'] === '0')
				$where [] = ' tmeth.id = \'' . $criteria ['training_method_id'] . '\'';

			if ($criteria ['training_lang1_id'] or $criteria ['training_lang1_id'] === '0')
				$where [] = ' pt.training_primary_language_option_id = \'' . $criteria ['training_lang1_id'] . '\'';

			if ($criteria ['training_lang2_id'] or $criteria ['training_lang2_id'] === '0')
				$where [] = ' pt.training_secondary_language_option_id = \'' . $criteria ['training_lang2_id'] . '\'';

			if ( $criteria['startdate'] ) {
					$parts = explode('/', $criteria['startdate']);
					$reformattedDate = implode('/', array( @$parts[1], @$parts[0], @$parts[2] ) ); // swap month and date (reverse them)
					$startDate = @date('Y-m-d',@strtotime($reformattedDate));

					$parts2 = explode('/', $criteria['enddate']);
					$reformattedDate = implode('/', array( @$parts2[1], @$parts2[0], @$parts2[2] ) ); // swap month and date (reverse them)
					$endDate = @date('Y-m-d',@strtotime($reformattedDate));

					if (! empty($startDate) && !empty($endDate) )
						$where [] = ' training_start_date >= \'' . $startDate . '\'  AND training_start_date <= \'' . $endDate . '\'  ';
			}

			if (intval ( $criteria ['is_tot'] ))
				$where [] = ' is_tot = ' . $criteria ['is_tot']; // not used

			if ($criteria ['training_funding_id'] or $criteria ['training_funding_id'] === '0')
				$where [] = ' tfund.training_funding_option_id = \'' . $criteria ['training_funding_id'] . '\'';

			if ($criteria ['training_category_id'] or $criteria ['training_category_id'] === '0')
				$where [] = ' tcat.id = \'' . $criteria ['training_category_id'] . '\'';

			if ($criteria ['training_got_id'] or $criteria ['training_got_id'] === '0')
				$where [] = ' tgotc.id = \'' . $criteria ['training_got_id'] . '\'';

			if ($criteria ['training_custom1_id'] or $criteria ['training_custom1_id'] === '0')
				$where [] = ' pt.training_custom_1_option_id = \'' . $criteria ['training_custom1_id'] . '\'';

			if ($criteria ['training_custom2_id'] or $criteria ['training_custom2_id'] === '0')
				$where [] = ' pt.training_custom_2_option_id = \'' . $criteria ['training_custom2_id'] . '\'';

			if ($criteria ['training_custom3_id'] or $criteria ['training_custom3_id'] === '0')
				$where [] = ' pt.custom_3 = \'' . $criteria ['training_custom3_id'] . '\'';

			if ($criteria ['training_custom4_id'] or $criteria ['training_custom4_id'] === '0')
				$where [] = ' pt.custom_4 = \'' . $criteria ['training_custom4_id'] . '\'';

			$where[] = ' evaluation.is_deleted = 0';
			$where[] = ' evaluation_response.is_deleted = 0';
			$where[] = ' evaluation_question.is_deleted = 0';
			$where[] = ' evaluation_question_response.is_deleted = 0';
			if ( $criteria['has_response'] )
				$where[] = ' evaluation_response.evaluation_to_training_id IS NOT NULL ';

			// finish
			if ($where)
				$sql .= ' WHERE ' . implode ( ' AND ', $where );

			$sql .= ' GROUP BY evaluation_question_response.id';

			$rowArray = $db->fetchAll ( $sql );
			// end training lookup

			// output csv if necessary
			if ($this->getParam ( 'outputType' ))
				$this->sendData ( $this->reportHeaders ( false, $rowArray ) );

			//done
		}
		// values for the view
		$this->viewAssignEscaped ( 'results', $rowArray );
		$this->view->assign ( 'count', count($rowArray) );
		$this->view->assign ( 'criteria', $criteria );

		//evaluations drop down
		$evaluationsArray = OptionList::suggestionList ( 'evaluation', 'title', false, false, false );
		$this->viewAssignEscaped ( 'evaluations', $evaluationsArray );
		//trainers
		$trainersArray = $db->fetchAll('select p.id,p.first_name,p.middle_name,p.last_name from trainer left join person p on p.id = person_id order by p.first_name asc');
		foreach ($trainersArray as $i => $row)
			$trainersArray[$i]['fullname'] = $this->setting('display_middle_name_last') ? $row['first_name'].' '.$row['last_name'].' '.$row['middle_name'] :  $row['first_name'].' '.$row['middle_name'].' '.$row['last_name'];
		$this->viewAssignEscaped ( 'trainers', $trainersArray );
		//locations
		$locations = Location::getAll();
		$this->viewAssignEscaped('locations', $locations);
		//course
		$courseArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $courseArray );
		//location drop-down
		$tlocations = TrainingLocation::selectAllLocations ($this->setting('num_location_tiers'));
		$this->viewAssignEscaped ( 'tlocations', $tlocations );
		//organizers
		$organizersArray = OptionList::suggestionList ( 'training_organizer_option', 'training_organizer_phrase', false, false, false );
		$this->viewAssignEscaped ( 'organizers', $organizersArray );
		//topics
		$topicsArray = OptionList::suggestionList ( 'training_topic_option', 'training_topic_phrase', false, false, false );
		$this->viewAssignEscaped ( 'topics', $topicsArray );
		//levels
		$levelArray = OptionList::suggestionList ( 'training_level_option', 'training_level_phrase', false, false );
		$this->viewAssignEscaped ( 'levels', $levelArray );
		//pepfar
		$organizersArray = OptionList::suggestionList ( 'training_pepfar_categories_option', 'pepfar_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'pepfars', $organizersArray );
		//refresher
		if( $this->setting('multi_opt_refresher_course') ){
			$refresherArray = OptionList::suggestionList ( 'training_refresher_option', 'refresher_phrase_option', false, false, false );
			$this->viewAssignEscaped ( 'refresher', $refresherArray );
		}
		//funding
		$fundingArray = OptionList::suggestionList ( 'training_funding_option', 'funding_phrase', false, false, false );
		$this->viewAssignEscaped ( 'funding', $fundingArray );
		//category
		$categoryArray = OptionList::suggestionList ( 'training_category_option', 'training_category_phrase', false, false, false );
		$this->viewAssignEscaped ( 'category', $categoryArray );
		//primary language
		$langArray = OptionList::suggestionList ( 'trainer_language_option', 'language_phrase', false, false, false );
		$this->viewAssignEscaped ( 'language', $langArray );
		//category+titles
		$categoryTitle = MultiAssignList::getOptions ( 'training_title_option', 'training_title_phrase', 'training_category_option_to_training_title_option', 'training_category_option' );
		$this->view->assign ( 'categoryTitle', $categoryTitle );
		//training methods
		$methodTitle = OptionList::suggestionList ( 'training_method_option', 'training_method_phrase', false, false, false );
		$this->view->assign ( 'methods', $methodTitle );
		//got curric
		$gotCuriccArray = OptionList::suggestionList ( 'training_got_curriculum_option', 'training_got_curriculum_phrase', false, false, false );
		$this->viewAssignEscaped ( 'gotcurric', $gotCuriccArray );
		//mechanism (organizer_partners table)
		$mechanismArray = array();
		if( $this->setting('display_training_partner') ){
			$mechanismArray = OptionList::suggestionList ( 'organizer_partners', 'mechanism_id', false, false, false, "mechanism_id != ''");
		}
		$this->viewAssignEscaped ( 'mechanisms', $mechanismArray );

		//customfields
		$customArray = OptionList::suggestionList ( 'training_custom_1_option', 'custom1_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom1', $customArray );
		$customArray2 = OptionList::suggestionList ( 'training_custom_2_option', 'custom2_phrase', false, false, false );
		$this->viewAssignEscaped ( 'custom2', $customArray2 );
		$customArray3 = OptionList::suggestionList ( 'training', 'custom_3', false, false, false, "custom_3 != ''" );
		$this->viewAssignEscaped ( 'custom3', $customArray3 );
		$customArray4 = OptionList::suggestionList ( 'training', 'custom_4', false, false, false, "custom_4 != ''" );
		$this->viewAssignEscaped ( 'custom4', $customArray4 );
		//$customArray5 = OptionList::suggestionList ( 'training', 'custom_5', false, false, false, "custom_5 != ''" );
		//$this->viewAssignEscaped ( 'custom5', $customArray5 );
		#$createdByArray = $db->fetchAll("select id,CONCAT(first_name, CONCAT(' ', last_name)) as name from user where is_blocked = 0");
		#$this->viewAssignEscaped ( 'createdBy', $createdByArray );
		#// find category based on title
		#$catId = NULL;
		#if ($criteria ['training_category_id']) {
		#	foreach ( $categoryTitle as $r ) {
		#		if ($r ['id'] == $criteria ['training_category_id']) {
		#			$catId = $r ['training_category_option_id'];
		#			break;
		#		}
		#	}
		#}
		#$this->view->assign ( 'catId', $catId );
		//done
	}

	public function psTrainingsByNameAction() {
		$this->view->assign ( 'mode', 'id' );
		#		return $this->trainingReport ();
	}

	public function psTrainingByParticipantsAction() {
		$this->view->assign ( 'mode', 'id' );
		#		return $this->trainingReport ();
	}

	private function institution_link_exists($join){
		$found = false;
		foreach ($join as $j){
			if ($j['table'] == "institution"){
				$found = true;
			}
		}

		return $found;
	}

	/**
	 * @param $params - query criteria
	 * @return array containing a Zend_Db_Select object and the column headers for output
	 */

	protected function psStudentReportsBuildQuery(&$params) {

		$headers = array();
		$headers[] = "ID";//TA:#217
		$headers[] = "First Name";
		$headers[] = "Last Name";
		$cohortJoined = false;
		$institutionJoined = false;
	    $linkstudentclassesJoined = false; //TA:#392
	    $reasonJoined = false;//TA:#405
	    $take_drop_reason = true;//TA:#405
	    $addressJoined = false; //TA:#496
	    $licensesJoined = false; //TA:#486
	    $relationshipJoined = false; //TA:#504
	    

		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$helper = new Helper();

		$s = $db->select()
			->from(array('p' => 'person'), array('p.id', 'p.first_name', 'p.last_name')) //TA:#217
			->joinInner(array('s' => 'student'), 's.personid = p.id', array())
			->where('p.is_deleted = 0');

		if ((isset($params['showProvince']) && $params['showProvince']) ||
			(isset($params['province_id']) && $params['province_id'])) {

		    if (!$institutionJoined) {
                $s->joinLeft(array('i' => 'institution'), 'i.id = s.institutionid', array());
                $institutionJoined = true;
            }

            $s->joinLeft(array('loc1' => 'location'), 'loc1.id = i.geography1', array());

			if (isset($params['showProvince']) && $params['showProvince']) {
				$headers[] = t("Region A (Province)");
				$s->columns('loc1.location_name');
			}
			if (isset($params['province_id']) && $params['province_id']) {
				$s->where('loc1.id IN (?)', $params['province_id']);
			}
		}

		if ((isset($params['showDistrict']) && $params['showDistrict']) ||
			(isset($params['district_id']) && $params['district_id'])) {
            if (!$institutionJoined) {
                $s->joinLeft(array('i' => 'institution'), 'i.id = s.institutionid', array());
                $institutionJoined = true;
            }

            $s->joinLeft(array('loc2' => 'location'), 'loc2.id = i.geography2', array());

            if (isset($params['showDistrict']) && $params['showDistrict']) {
                $headers[] = t("Region B (Health District)");
                $s->columns('loc2.location_name');
            }
            if (isset($params['district_id']) && $params['district_id']) {
                $ids = "";
                foreach ($params['district_id'] as $l) {
                    $ids .= array_pop(explode('_', $l)) .", ";
                }
                $ids = trim($ids, ', ');
                $s->where('loc2.id IN (?)', $ids);
            }
		}

        if ((isset($params['showRegionC']) && $params['showRegionC']) ||
            (isset($params['region_c_id']) && $params['region_c_id'])) {
            if (!$institutionJoined) {
                $s->joinLeft(array('i' => 'institution'), 'i.id = s.institutionid', array());
                $institutionJoined = true;
            }

            $s->joinLeft(array('loc3' => 'location'), 'loc3.id = i.geography3', array());

            if (isset($params['showRegionC']) && $params['showRegionC']) {
                $headers[] = t("Region C (Local Region)");
                $s->columns('loc3.location_name');
            }
            if (isset($params['region_c_id']) && $params['region_c_id']) {
                $ids = "";
                foreach ($params['region_c_id'] as $l) {
                    $ids .= array_pop(explode('_', $l)) .", ";
                }
                $ids = trim($ids, ', ');
                $s->where('loc3.id IN (?)', $ids);
            }
        }

        if (isset($params['institution']) && $params['institution'] ||
			isset($params['showinstitution']) && $params['showinstitution']) {

			//TA:#247 use link_student_cohort to get institution
			    if (!$cohortJoined) {
 			$s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
 			$s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
 			$cohortJoined = true;
			    }
			///////
			if (!$institutionJoined) {
			    //TA:#247 use link_student_cohort to get institution
			    //$s->joinLeft(array('i' => 'institution'), 'i.id = s.institutionid', array());
			    $s->joinLeft(array('i' => 'institution'), 'i.id = c.institutionid', array());
			    $institutionJoined = true;
			}

			if (isset($params['showinstitution']) && $params['showinstitution']) {
			    $headers[] = "Institution";
			    $s->columns('i.institutionname');
			}
			if (isset($params['institution']) && $params['institution']) {
			    $s->where('i.id = ?', $params['institution']);
			}

		}

		if (isset($params['cohort']) && $params['cohort'] ||
			isset($params['showcohort']) && $params['showcohort']) {
			    //TA:#247 use table joinonly ones
			if (!$cohortJoined) {
			     $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
			 $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
			 $cohortJoined = true;
			}
			if (isset($params['showcohort']) && $params['showcohort']) {
			//TA:#217
			    if((isset($params['show_old_cohorts']))){
			        $headers[] = "Old Cohorts";
			        $s->columns('GROUP_CONCAT(DISTINCT c.cohortname) as old_cohortname');
			        $s->group('p.id');
			        if ($cohortJoined){
			         $s->where('lsc.dropdate != ?','0000-00-00');
			        }
			    }else if((isset($params['show_current_cohort']))){
			        $take_drop_reason = false;//TA:#405
			        $headers[] = "Current Cohorts";
			        $s->columns('GROUP_CONCAT(DISTINCT c.cohortname) as current_cohortname');
			        $s->group('p.id');
			        if ($cohortJoined){
			         $s->where('lsc.dropdate = ?','0000-00-00');
			        }
			    }else{
				    $headers[] = "Cohort";
				    $s->columns('c.cohortname');
			    }
			}
			if (isset($params['cohort']) && $params['cohort']) {
				$s->where('c.id = ?', $params['cohort']);
			}
			//TA  filter cohort by institution access as well
  			$uid = $helper->myid();
 			$user_institutions = $helper->getUserInstitutions($uid);
 		    if (!empty($user_institutions)) {
 			   $s->where("c.institutionid IN (SELECT institutionid FROM link_user_institution WHERE userid = ?)", $uid);
 		    }
		}
		else{
		    //TA:#217 show students with only current cohort (avoid duplications students names in report)
		    $s->distinct(true);
		}

		if (isset($params['cadre']) && $params['cadre'] ||
			isset($params['showcadre']) && $params['showcadre']) {

			//TA:#247 use link_student_cohort and cohort to get cadres
			// do not use to get cadre by student table, beacuse when new student is added cadre is 0 by defualt
			//and when we assign student to cohort new record in added only to link_student_cohort table, but student.cadre value is not updated
			//$s->joinLeft(array('ca' => 'cadres'), 'ca.id = s.cadre', array());
			if (!$cohortJoined) {
			     $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
			     $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
			     $cohortJoined = true;
			}
			$s->joinLeft(array('ca' => 'cadres'), 'ca.id = c.cadreid', array());

			if (isset($params['showcadre']) && $params['showcadre']) {
				$headers[] = "Cadre";
				$s->columns('ca.cadrename');
			}
			if (isset($params['cadre']) && $params['cadre']) {
				$s->where('ca.id = ?', $params['cadre']);
			}
		}

		if (isset($params['showyearinschool']) && $params['showyearinschool']) {
			if (!$cohortJoined) {
				$s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
				$s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
				$cohortJoined = true;
			}
			$s->columns('c.startdate');
			$headers[] = "Start Date";
			if (isset($params['yearinschool']) && $params['yearinschool']) {
				$s->where($db->quoteInto("c.startdate LIKE ?", substr($params['yearinschool'], 0, 4) . '%'));
			}
		}

		if (isset($params['showgender']) && $params['showgender']) {
			$s->columns('p.gender');
			$headers[] = "Gender";
		}
		if (isset($params['gender']) && $params['gender']) {
			$gender_id = $params['gender'];
			if ($gender_id > 0) {
				$gender_arr = array(1 => 'male', 2 => 'female');
				$s->where('p.gender = ?', $gender_arr[$gender_id]);
			}
		}

		//TA:#251
		if ((isset($params['show_marital_status'])) && $params['show_marital_status']) {
		    $headers[] = t("Marital Status");
		    $s->columns('p.marital_status');
		}

		if ((isset($params['shownationality'])) && $params['shownationality'] ||
			(isset($params['nationality']) && $params['nationality'])) {

			$s->joinLeft(array('ln' => 'lookup_nationalities'), 'ln.id = s.nationalityid', array());
			if (isset($params['shownationality']) && $params['shownationality']) {
				$headers[] = "Nationality";
				$s->columns('ln.nationality');
			}
			if (isset($params['nationality']) && $params['nationality']) {
				$s->where('ln.id = ?', $params['nationality']);
			}
		}
		
		
		//TA:#492
		if ((isset($params['shownationalid'])) && $params['shownationalid'] ) {
		    $headers[] = "Student ID";
		    $s->columns('p.national_id');
		}
		
		
		//TA:#400
		if ((isset($params['showindexnumber'])) && $params['showindexnumber'] ) {
		    $headers[] = "Index Number";
		    $s->columns('s.index_number');
		 }

		//TA:#251
		if ((isset($params['showdob'])) && $params['showdob']) {
		    $headers[] = t("Date of Birth");
		    $s->columns('p.birthdate');
		}

		if ((isset($params['showage']) && $params['showage']) ||
			(isset($params['agemin']) && $params['agemin']) ||
			(isset($params['agemax']) && $params['agemax'])) {

			if (isset($params['showage']) && $params['showage']) {
				$headers[] = "Age";
				$s->columns(new Zend_Db_Expr("DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(p.birthdate)), '%Y')+0 AS age"));

				if (isset($params['agemin']) && $params['agemin']) {
					$s->having('age >= ?', $params['agemin']);
				}
				if (isset($params['agemax']) && $params['agemax']) {
					$s->having('age <= ?', $params['agemax']);
				}

			}
			else {
				$ageExpr = new Zend_Db_Expr("DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(p.birthdate)), '%Y')+0");
				if (isset($params['agemin']) && $params['agemin']) {
					$s->where($ageExpr . ' >= ?', $params['agemin']);
				}
				if (isset($params['agemax']) && $params['agemax']) {
					$s->where($ageExpr . ' <= ?', $params['agemax']);
				}
			}
		}

		//TA:#334 
		//TA:#391 apply those conditions only for reports where we have showactive and showterminated checkboxes
		if(isset($params['action']) && ($params['action'] === 'ps-students-by-name' || $params['action'] === 'ps-students-trained')){
		if (isset($params['showactive']) && $params['showactive']) {
			if (isset($params['showterminated']) && $params['showterminated']) {//active=on, terminated=on => show both active and dropped students with termination reasons (=8499) 
			    if (!$cohortJoined) {
			        $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
			        $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
			        $cohortJoined = true;
			    }
			    if (!$reasonJoined) {
			     $s->joinLeft(array('lr' => 'lookup_reasons'), 'lr.id = lsc.dropreason', array());
			    }
			    $s->where("p.active = 'active'");
			    $s->columns("lr.reason");
			    $headers[] = "Terminated Early";
			}else{//active=on, terminated=off => show active students, excluding dropped students (=8231)
			    if (!$cohortJoined) {
		            $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		            $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		            $cohortJoined = true;
		        }
		        $s->where("lsc.isgraduated = 0");
		        $s->where("lsc.dropdate = '0000-00-00'");
		        $s->where("p.active = 'active'");
			}
		}else{
		    if (isset($params['showterminated']) && $params['showterminated']) {//active=off, terminated=on => Show only terminated students  with termination reasons (=131) (excluding reason 'Upgrading')
		        if (!$cohortJoined) {
		            $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		            $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		            $cohortJoined = true;
		        }
		        if(!$reasonJoined){//TA:#405
		          $s->joinLeft(array('lr' => 'lookup_reasons'), 'lr.id = lsc.dropreason', array());
		        }
		        $s->where("lsc.isgraduated = 0");
		        $s->where("lsc.dropdate != '0000-00-00'");
		        $s->where("lr.reasontype = 'drop'"); //we need to take only drop reason
		        $s->where("lr.reason != 'Upgrading'"); //we need exclude student who has 'Upgrading' reson
		        $s->columns("lr.reason");
		        $headers[] = "Terminated Early";
		    }else{//active=off, terminated=off => Show active students, excluding dropped students (=8231)
		        if (!$cohortJoined) {
		            $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		            $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		            $cohortJoined = true;
		        }
		        $s->where("lsc.isgraduated = 0");
		        $s->where("lsc.dropdate = '0000-00-00'");
		        $s->where("p.active = 'active'");
		    } 
		}
		}

		if ((isset($params['showdegrees'])) && $params['showdegrees'] ||
			(isset($params['degrees']) && $params['degrees'])) {

			if (!$institutionJoined) {
				$s->joinLeft(array('i' => 'institution'), 'i.id = s.institutionid', array());
				$institutionJoined = true;
			}

			$s->joinLeft(array('liddeg' => 'link_institution_degrees'), 'liddeg.id_institution = i.id', array());
			$s->joinLeft(array('ldeg' => 'lookup_degrees'), 'ldeg.id = liddeg.id_degree', array());

			if ((isset($params['showdegrees'])) && $params['showdegrees']) {
				$headers[] = "Degree";
				$s->columns('ldeg.degree');
			}
			if (isset($params['degrees']) && $params['degrees']) {
				$s->where('ldeg.id = ?', $params['degrees']);
			}
		}

		if (isset($params['showgraduated']) && $params['showgraduated']) {
			if (!$cohortJoined) {
				$s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
				$s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
				$cohortJoined = true;
			}
			$s->columns("IF(lsc.isgraduated = 1, 'Graduated', '')");
			$headers[] = "Graduated";
			$s->where("lsc.isgraduated = 1");;
		}

		//TA:#389
		if ((isset($params['showfunding']) && $params['showfunding']) ||
		    (isset($params['funding']) && $params['funding'])) {
			$s->joinLeft(array('lsf' => 'link_student_funding'), 'lsf.studentid = s.id', array());
			$s->joinLeft(array('lf' => 'lookup_fundingsources'), 'lf.id = lsf.fundingsource', array());
			//$s->columns('lf.fundingname');
			//TA:103 to display multiple sources for one person in one row
			//TA:#251 to display amount of funding also
			$s->columns("GROUP_CONCAT( ' ' , lf.fundingname, ': ',lsf.fundingamount)");
			//TA: we commented it for #405, but it does not work for GROUP_CONCAT
			$s->group('p.id');
			if((isset($params['funding']) && $params['funding'])){
			 $s->where('lf.id=' . $params['funding']);
			}
			$headers[] = "Funding";
		}
		
		//TA:#405
		if ((isset($params['showreasonsep']) && $params['showreasonsep']) ||
		    (isset($params['reasonsep']) && $params['reasonsep'])) {
		        if (!$cohortJoined) {
		            $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		            $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		            $cohortJoined = true;
		        }
		        if (!$reasonJoined) {
		          $s->joinLeft(array('lr' => 'lookup_reasons'), 'lr.id = lsc.dropreason', array());
		        }
		        $s->columns('lr.reason');
		        if((isset($params['reasonsep']) && $params['reasonsep']) &&  $take_drop_reason === true){
		            $s->where('lr.id=' . $params['reasonsep']);
		        }
		        $headers[] = "Reason for Separation";
		    }
		    //////
		    
		    //TA:#458 START
		    $start_date_sep = '';
		    if((isset($params['startdaysep']) && $params['startdaysep']) &&
		        (isset($params['startmonthsep']) && $params['startmonthsep']) &&
		        (isset($params['startyearsep']) && $params['startyearsep'])) {
		            if (!$cohortJoined) {
		                $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		                $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		                $cohortJoined = true;
		            }
		            $start_date_sep = $params['startyearsep'].'-'.$params['startmonthsep'].'-'.$params['startdaysep'];
		        }
		    
		        $end_date_sep = '';
		        if ((isset($params['enddaysep']) && $params['enddaysep']) &&
		            (isset($params['endmonthsep']) && $params['endmonthsep']) &&
		            (isset($params['endyearsep']) && $params['endyearsep'])) {
		                if (!$cohortJoined) {
		                    $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		                    $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		                    $cohortJoined = true;
		                }
		                $end_date_sep = $params['endyearsep'].'-'.$params['endmonthsep'].'-'.$params['enddaysep'];
		            }
		            if ((isset($params['showdatesep']) && $params['showdatesep']) || ($start_date_sep !== '') || ($end_date_sep !== '')) {
		                if (!$cohortJoined) {
		                    $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		                    $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		                    $cohortJoined = true;
		                }
		                $s->columns('lsc.dropdate');
		                $headers[] = "Date of Separation";
		                if ($start_date_sep !== '') {
		                    $s->where('lsc.dropdate >= ?', $start_date_sep);
		                }
		                if ($end_date_sep !== '') {
		                    $s->where('lsc.dropdate <= ?', $end_date_sep);
		                }
		            }
		      //TA:#458 END

		    //TA: this is facility at GRADUATION    !!!!!!!
		if ((isset($params['showfacility']) && $params['showfacility']) ||
			(isset($params['facility']) && $params['facility'])) {
			//TA:#431 it seems that this table isnot using any more 
			//$s->joinLeft(array('lsfac' => 'link_student_facility'), 'lsfac.id_student = s.id', array());
			//$s->joinLeft(array('fac' => 'facility'), 'fac.id = lsfac.id_facility', array());
			$s->joinLeft(array('fac' => 'facility'), 'fac.id = s.postfacilityname', array());

			if ((isset($params['showfacility']) && $params['showfacility'])) {
				$s->columns('fac.facility_name');
				$headers[] = "Facility";
			}
			if (isset($params['facility']) && $params['facility']) {
				$s->where('fac.id = ?', $params['facility']);
			}
		}

		if ((isset($params['showtutor']) && $params['showtutor']) ||
			(isset($params['tutor']) && $params['tutor'])) {
			$s->joinLeft(array('tut' => 'tutor'), 'tut.id = s.advisorid', array()); //TA:#337
			$s->joinLeft(array('tutp' => 'person'), 'tutp.id = tut.personid', array());

			if (isset($params['tutor']) && $params['tutor']) {
				$s->where('tut.id = ?', $params['tutor']);
			}

			if (isset($params['showtutor']) && $params['showtutor']) {
				$s->columns("CONCAT(tutp.first_name,' ',tutp.last_name) AS tutor_name");
				$headers[] = "Tutor Advisor";
			}
		}

		$start_date = '';
		if((isset($params['startday']) && $params['startday']) &&
			(isset($params['startmonth']) && $params['startmonth']) &&
			(isset($params['startyear']) && $params['startyear'])) {
			if (!$cohortJoined) {
				$s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
				$s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
				$cohortJoined = true;
			}
			$start_date = $params['startyear'].'-'.$params['startmonth'].'-'.$params['startday'];
		}

		$end_date = '';
		if ((isset($params['endday']) && $params['endday']) &&
			(isset($params['endmonth']) && $params['endmonth']) &&
			(isset($params['endyear']) && $params['endyear'])) {
			if (!$cohortJoined) {
				$s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
				$s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
				$cohortJoined = true;
			}
			$end_date = $params['endyear'].'-'.$params['endmonth'].'-'.$params['endday'];
		}

		//TA:#251 show start date as well
		if ((isset($params['showstartdate']) && $params['showstartdate']) || ($start_date !== '') || ($end_date !== '')) {
			$s->columns('c.startdate');
			$headers[] = "Start Date";
			if ($start_date !== '') {
				$s->where('c.startdate >= ?', $start_date);
			}
			if ($end_date !== '') {
				$s->where('c.startdate <= ?', $end_date);
			}
		}

		//TA:#251 show grad date as well
		$grad_start_date = '';
		if((isset($params['gradstartday']) && $params['gradstartday']) &&
		    (isset($params['gradstartmonth']) && $params['gradstartmonth']) &&
		    (isset($params['gradstartyear']) && $params['gradstartyear'])) {
		        if (!$cohortJoined) {
		            $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		            $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		            $cohortJoined = true;
		        }
		        $grad_start_date = $params['gradstartyear'].'-'.$params['gradstartmonth'].'-'.$params['gradstartday'];
		    }

		    $grad_end_date = '';
		    if ((isset($params['gradendday']) && $params['gradendday']) &&
		        (isset($params['gradendmonth']) && $params['gradendmonth']) &&
		        (isset($params['gradendyear']) && $params['gradendyear'])) {
		            if (!$cohortJoined) {
		                $s->joinLeft(array('lsc' => 'link_student_cohort'), 'lsc.id_student = s.id', array());
		                $s->joinLeft(array('c' => 'cohort'), 'c.id = lsc.id_cohort', array());
		                $cohortJoined = true;
		            }
		            $grad_end_date = $params['gradendyear'].'-'.$params['gradendmonth'].'-'.$params['gradendday'];
		        }
		if ((isset($params['showgraduation']) && $params['showgraduation']) || ($grad_start_date !== '') || ($grad_end_date !== '')) {
		    $s->columns('c.graddate');
		    $headers[] = "Graduation Date";
		    if ($grad_start_date !== '') {
		        $s->where('c.graddate >= ?', $grad_start_date);
		    }
		    if ($grad_end_date !== '') {
		        $s->where('c.graddate <= ?', $grad_end_date);
		    }
		}
		
		//TA:#392
		if ((isset($params['shownamedate'])) && $params['shownamedate'] ) {
		    if (!$linkstudentclassesJoined) {
		        $s->joinLeft(array('lscl' => 'link_student_classes'), 'lscl.studentid=s.id', array());
		        $linkstudentclassesJoined = true;
		    }
 		    $s->joinLeft(array('cl' => 'classes'), 'cl.id=lscl.classid', array());
 		    $headers[] = "Course Name";
 		    $headers[] = "Grade";
 		    $s->columns("cl.classname");
 		    $s->columns("lscl.grade");
		}
		
		
		//TA:#496 STUDENT ADDRESS REPORT: student contact address: province
		if ((isset($params['show_contactProvince']) && $params['show_contactProvince']) ||
		    (isset($params['contact_province_id']) && $params['contact_province_id'])) {
		        $s->joinLeft(array('contact_loc1' => 'location'), 'contact_loc1.id = s.geog1', array());
		        if (isset($params['show_contactProvince']) && $params['show_contactProvince']) {
		            $headers[] = t('Contact Address') . " " . t("Region A (Province)");
		            $s->columns('contact_loc1.location_name as province');
		        }
		        if (isset($params['contact_province_id']) && $params['contact_province_id']) {
		            $s->where('contact_loc1.id IN (?)', $params['contact_province_id']);
		        }
		    }
		    
		 //TA:#496 STUDENT ADDRESS REPORT: student contact address: district
		 if ((isset($params['show_contactDistrict']) && $params['show_contactDistrict']) ||
		        (isset($params['contact_district_id']) && $params['contact_district_id'])) {
		            $s->joinLeft(array('contact_loc2' => 'location'), 'contact_loc2.id = s.geog2', array());
		            if (isset($params['show_contactDistrict']) && $params['show_contactDistrict']) {
		                $headers[] =  t('Contact Address') . " " . t("Region B (Health District)");
		                $s->columns('contact_loc2.location_name as district');
		            }
		            if (isset($params['contact_district_id']) && $params['contact_district_id']) {
		                $ids = "";
		                foreach ($params['contact_district_id'] as $l) {
		                    $ids .= array_pop(explode('_', $l)) .", ";
		                }
		                $ids = trim($ids, ', ');
		                $s->where('contact_loc2.id IN (?)', $ids);
		            }
		        }
		        
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: region 
		  if ((isset($params['show_contactRegionC']) && $params['show_contactRegionC']) ||
		            (isset($params['contact_region_c_id']) && $params['contact_region_c_id'])) {
		                $s->joinLeft(array('contact_loc3' => 'location'), 'contact_loc3.id = s.geog2', array());
		                if (isset($params['show_contactRegionC']) && $params['show_contactRegionC']) {
		                    $headers[] =  t('Contact Address') . " " . t("Region C (Local Region)");
		                    $s->columns('contact_loc3.location_name as region');
		                }
		                if (isset($params['contact_region_c_id']) && $params['contact_region_c_id']) {
		                    $ids = "";
		                    foreach ($params['contact_region_c_id'] as $l) {
		                        $ids .= array_pop(explode('_', $l)) .", ";
		                    }
		                    $ids = trim($ids, ', ');
		                    $s->where('contact_loc3.id IN (?)', $ids);
		                }
		            }
		            
	//TA:#496 STUDENT ADDRESS REPORT: student contact address: address 1 
		  if ((isset($params['show_address1']) && $params['show_address1'])) {
		      $headers[] =  t('Contact') . " " . t("Address") . " 1";
		      $s->columns('p.home_address_1');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: address 2
		  if ((isset($params['show_address2']) && $params['show_address2'])) {
		      $headers[] =  t('Contact') . " " . t("Address") . " 2";
		      $s->columns('p.home_address_2');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: city
		  if ((isset($params['show_city']) && $params['show_city'])) {
		      $headers[] =  t('Contact Address') . " " . t("City");
		      $s->columns('p.home_city');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: zip
		  if ((isset($params['show_zip']) && $params['show_zip'])) {
		      $headers[] =  t('Contact Address') . " " . t('Postal Code');
		      $s->columns('p.home_postal_code');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: phone
		  if ((isset($params['show_phone']) && $params['show_phone'])) {
		      $headers[] =  t('Contact Address') . " " . t("Phone");
		      $s->columns('p.phone_work');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: cell phone
		  if ((isset($params['show_cellphone']) && $params['show_cellphone'])) {
		      $headers[] =  t('Contact Address') . " " . t("Cellphone");
		      $s->columns('p.phone_mobile');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: cell phone 2
		  if ((isset($params['show_cellphone2']) && $params['show_cellphone2'])) {
		      $headers[] =  t('Contact Address') . " " . t("Cellphone") . " 2";
		      $s->columns('p.phone_home');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: email
		  if ((isset($params['show_email']) && $params['show_email'])) {
		      $headers[] =  t('Contact Address') . " " . t("Email");
		      $s->columns('p.email');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: email 2
		  if ((isset($params['show_email2']) && $params['show_email2'])) {
		      $headers[] =  t('Contact Address') . " " . t("Email") . " 2";
		      $s->columns('p.email_secondary');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: emergency contact
		  if ((isset($params['show_emercontact']) && $params['show_emercontact'])) {
		      $headers[] =  t('Contact Address') . " " . t("Emergency Contact");
		      $s->columns('s.emergcontact');
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student contact address: is address residential
		  if ((isset($params['show_resaddress']) && $params['show_resaddress'])) {
		      $headers[] =  t('Contact') . " " . t("Residential Address") . "?";
		      $s->columns("p.home_is_residential");
		    //  $s->columns(CASE WHEN p.home_is_residential=1 THEN 'Yes' END as aaa);   
		  }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student kin info: name
		  if ((isset($params['show_kinname']) && $params['show_kinname'])) {
		      if (!$addressJoined) {
		          $s->joinLeft(array('lsa' => 'link_student_addresses'), 'lsa.id_student=s.id', array());
		          $s->joinLeft(array('permanent_address' => 'addresses'), 'permanent_address.id=lsa.id_address', array());
		          $addressJoined = true;
		      }
		      $headers[] =  t('Next of Kin/Guardian Address') . " " . t("Name");
		      $s->columns('lsa.kin_name');
		  }
		  
	   //TA:#496, TA:#504 STUDENT ADDRESS REPORT: student kin info: relationship
		  if ((isset($params['show_kinrelationship']) && $params['show_kinrelationship'])||
		    (isset($params['kin_relationship']) && $params['kin_relationship'])) {
		      if (!$addressJoined) {
		          $s->joinLeft(array('lsa' => 'link_student_addresses'), 'lsa.id_student=s.id', array());
		          $s->joinLeft(array('permanent_address' => 'addresses'), 'permanent_address.id=lsa.id_address', array());
		          $addressJoined = true;
		      }
		      if (!$relationshipJoined) {
		          $s->joinLeft(array('lr' => 'lookup_relationship'), 'lr.id=lsa.kin_relationship', array());
		          $relationshipJoined = true;
		      }
		      if (isset($params['show_kinrelationship']) && $params['show_kinrelationship']) {
		          $headers[] = t('Next of Kin/Guardian Address') . " " . "Relationship";
		          $s->columns('lr.relationship');
		      }
		      if (isset($params['kin_relationship']) && $params['kin_relationship']) {
		          $s->where("lsa.kin_relationship = '" . $params['kin_relationship'] . "'");
		      }
		  }
		  
	//TA:#496 STUDENT ADDRESS REPORT: student kin info: province
		if ((isset($params['show_kinProvince']) && $params['show_kinProvince']) ||
		    (isset($params['kin_province_id']) && $params['contactkin_province_id_province_id'])) {
		        $s->joinLeft(array('kin_loc1' => 'location'), 'kin_loc1.id = s.geog1', array());
		        if (isset($params['show_kinProvince']) && $params['show_kinProvince']) {
		            $headers[] = t('Next of Kin/Guardian Address') . " " . t("Region A (Province)");
		            $s->columns('kin_loc1.location_name as kinprovince');
		        }
		        if (isset($params['kin_province_id']) && $params['kin_province_id']) {
		            $s->where('kin_loc1.id IN (?)', $params['kin_province_id']);
		        }
		    }
		    
		 //TA:#496 STUDENT ADDRESS REPORT: student kin info: district
		 if ((isset($params['show_kinDistrict']) && $params['show_kinDistrict']) ||
		        (isset($params['kin_district_id']) && $params['kin_district_id'])) {
		            $s->joinLeft(array('kin_loc2' => 'location'), 'kin_loc2.id = s.geog2', array());
		            if (isset($params['show_kinDistrict']) && $params['show_kinDistrict']) {
		                $headers[] =  t('Next of Kin/Guardian Address') . " " . t("Region B (Health District)");
		                $s->columns('kin_loc2.location_name as district');
		            }
		            if (isset($params['kin_district_id']) && $params['kin_district_id']) {
		                $ids = "";
		                foreach ($params['kin_district_id'] as $l) {
		                    $ids .= array_pop(explode('_', $l)) .", ";
		                }
		                $ids = trim($ids, ', ');
		                $s->where('kin_loc2.id IN (?)', $ids);
		            }
		        }
		        
		  //TA:#496 STUDENT ADDRESS REPORT: student kin info: region 
		  if ((isset($params['show_kinRegionC']) && $params['show_kinRegionC']) ||
		            (isset($params['kin_region_c_id']) && $params['kin_region_c_id'])) {
		                $s->joinLeft(array('kin_loc3' => 'location'), 'kin_loc3.id = s.geog2', array());
		                if (isset($params['show_kinRegionC']) && $params['show_kinRegionC']) {
		                    $headers[] =  t('Next of Kin/Guardian Address') . " " . t("Region C (Local Region)");
		                    $s->columns('kin_loc3.location_name as region');
		                }
		                if (isset($params['kin_region_c_id']) && $params['kin_region_c_id']) {
		                    $ids = "";
		                    foreach ($params['kin_region_c_id'] as $l) {
		                        $ids .= array_pop(explode('_', $l)) .", ";
		                    }
		                    $ids = trim($ids, ', ');
		                    $s->where('kin_loc3.id IN (?)', $ids);
		                }
		            }
		  
		  //TA:#496 STUDENT ADDRESS REPORT: student kin info: address 1
		  if ((isset($params['show_kinaddress1']) && $params['show_kinaddress1'])) {
		          if (!$addressJoined) {
		              $s->joinLeft(array('lsa' => 'link_student_addresses'), 'lsa.id_student=s.id', array());
		              $s->joinLeft(array('permanent_address' => 'addresses'), 'permanent_address.id=lsa.id_address', array());
		              $addressJoined = true;
		          }
		              $headers[] = t('Next of Kin/Guardian Address') . " " .  t("Address") . " 1";
		              $s->columns('permanent_address.address1');
		      }
		      
		      //TA:#496 STUDENT ADDRESS REPORT: student kin info: address 2
		      if ((isset($params['show_kinaddress2']) && $params['show_kinaddress2'])) {
		          if (!$addressJoined) {
		              $s->joinLeft(array('lsa' => 'link_student_addresses'), 'lsa.id_student=s.id', array());
		              $s->joinLeft(array('permanent_address' => 'addresses'), 'permanent_address.id=lsa.id_address', array());
		              $addressJoined = true;
		          }
		          $headers[] = t('Next of Kin/Guardian Address') . " " .  t("Address") . " 2";
		          $s->columns('permanent_address.address2');
		      }
		      
		      //TA:#496 STUDENT ADDRESS REPORT: student kin info: city
		      if ((isset($params['show_kincity']) && $params['show_kincity'])) {
		          if (!$addressJoined) {
		              $s->joinLeft(array('lsa' => 'link_student_addresses'), 'lsa.id_student=s.id', array());
		              $s->joinLeft(array('permanent_address' => 'addresses'), 'permanent_address.id=lsa.id_address', array());
		              $addressJoined = true;
		          }
		          $headers[] = t('Next of Kin/Guardian Address') . " " .  t("City");
		          $s->columns('permanent_address.city');
		      }
		      
		      //TA:#496 STUDENT ADDRESS REPORT: student kin info: zip
		      if ((isset($params['show_kinzip']) && $params['show_kinzip'])) {
		          if (!$addressJoined) {
		              $s->joinLeft(array('lsa' => 'link_student_addresses'), 'lsa.id_student=s.id', array());
		              $s->joinLeft(array('permanent_address' => 'addresses'), 'permanent_address.id=lsa.id_address', array());
		              $addressJoined = true;
		          }
		          $headers[] = t('Next of Kin/Guardian Address') . " " .  t("Postal Code / ZIP");
		          $s->columns('permanent_address.postalcode');
		      }
		      
		      //TA:#496 STUDENT ADDRESS REPORT: student kin info: phone
		      if ((isset($params['show_kinphone']) && $params['show_kinphone'])) {
		          if (!$addressJoined) {
		              $s->joinLeft(array('lsa' => 'link_student_addresses'), 'lsa.id_student=s.id', array());
		              $s->joinLeft(array('permanent_address' => 'addresses'), 'permanent_address.id=lsa.id_address', array());
		              $addressJoined = true;
		          }
		          $headers[] = t('Next of Kin/Guardian Address') . " " .  t("Phone");
		          $s->columns('permanent_address.phone');
		      }
		      
		      //TA:#496 STUDENT ADDRESS REPORT: Home District Address: Home District
		      if ((isset($params['show_homedistrict']) && $params['show_homedistrict'])) {
		          $headers[] = t('Home District Address') . " " .  t("Home District");
		          $s->columns('p.home_district');
		      }
		      
		      //TA:#496 STUDENT ADDRESS REPORT: Home District Address: TA
		      if ((isset($params['show_ta']) && $params['show_ta'])) {
		          $headers[] = t('Home District Address') . " " .  t("TA");
		          $s->columns('p.ta');
		      }
		      
		      //TA:#496 STUDENT ADDRESS REPORT: Home District Address: TA
		      if ((isset($params['show_gvh']) && $params['show_gvh'])) {
		          $headers[] = t('Home District Address') . " " .  t("Village");
		          $s->columns('p.gvh');
		      }
		      
		      //TA:#486 STUDENT LICENSING REPORT: License
		      if ((isset($params['show_licenses']) && $params['show_licenses'])) {
		          if (!$licensesJoined) {
		              $s->joinLeft(array('lsl' => 'link_student_licenses'), 'lsl.studentid=s.id', array());
		              $s->joinLeft(array('licenses' => 'licenses'), 'licenses.id=lsl.licenseid', array());
		              $licensesJoined = true;
		          }
		          $headers[] = t('License Name');
		          $s->columns('licenses.licensename');
		      }
		      
		      //TA:#486 STUDENT LICENSING REPORT: Date
		      if ((isset($params['show_licensedate']) && $params['show_licensedate'])) {
		          if (!$licensesJoined) {
		              $s->joinLeft(array('lsl' => 'link_student_licenses'), 'lsl.studentid=s.id', array());
		              $s->joinLeft(array('licenses' => 'licenses'), 'licenses.id=lsl.licenseid', array());
		              $licensesJoined = true;
		          }
		          $headers[] = t('License Date');
		          $s->columns('licenses.licensedate');
		      }
		      
		      //TA:#486 STUDENT LICENSING REPORT: License Grade
		      if ((isset($params['show_licensegrade']) && $params['show_licensegrade'])) {
		          if (!$licensesJoined) {
		              $s->joinLeft(array('lsl' => 'link_student_licenses'), 'lsl.studentid=s.id', array());
		              $s->joinLeft(array('licenses' => 'licenses'), 'licenses.id=lsl.licenseid', array());
		              $licensesJoined = true;
		          }
		          $headers[] = t('License Grade');
		          $s->columns('lsl.grade');
		      }
		      
		      //TA:#486 STUDENT LICENSING REPORT: Grade Desciption
		      if ((isset($params['show_licensedescription']) && $params['show_licensedescription'])) {
		          if (!$licensesJoined) {
		              $s->joinLeft(array('lsl' => 'link_student_licenses'), 'lsl.studentid=s.id', array());
		              $s->joinLeft(array('licenses' => 'licenses'), 'licenses.id=lsl.licenseid', array());
		              $licensesJoined = true;
		          }
		          $headers[] = t('License Grade Desciption');
		          $s->columns('lsl.grade_description');
		      }
		      
		//TA:#433
		$login_user_id = $helper->myid();
		$ins_results = $helper->getUserInstitutions($login_user_id);
		if( !empty($ins_results) ){
		    $s->where("p.id in (select personid from student where institutionid in (SELECT institutionid FROM link_user_institution WHERE userid = {$login_user_id}))");
		}
	  //  print $s;
		return(array($s, $headers));
	}

	public function psStudentsTrainedAction() {
		$this->viewAssignEscaped ('locations', Location::getAll());

		$helper = new Helper();
		$this->view->assign('mode', 'id');
		$this->view->assign('institutions', $helper->getInstitutions(false));//TA:#433
		$this->view->assign('cadres', $helper->getCadres());
		$this->view->assign('institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign('cohorts', $helper->getCohorts());
		$this->view->assign('nationalities', $helper->getNationalities());
		$this->view->assign('funding', $helper->getFunding());
		$this->view->assign('tutors', $helper->getTutorsForUser($helper->myid()));//TA:#507
		$this->view->assign('facilities', $helper->getFacilities());
		$this->view->assign('coursetypes', $helper->AdminCourseTypes());
		$this->view->assign('degrees', $helper->getDegrees());
		$this->view->assign('site_style', $this->setting('site_style'));
        $this->view->assign('termination_statuses', array('1' => t('Any Status'), '2' => t('Only Early Termination')));

		if ($this->getSanParam('process')) {
			$criteria = $this->getAllParams();
			
			list($query, $headers) = $this->psStudentReportsBuildQuery($criteria);
		
			$db = Zend_Db_Table_Abstract::getDefaultAdapter();
			//print $query;
			$rowArray = $db->fetchAll($query);
			$this->view->assign('query', $query->__toString());

			$this->viewAssignEscaped("headers", $headers);
			$this->viewAssignEscaped("output", $rowArray);

			$this->view->assign('criteria', $criteria);
		}
	}
	
	//TA:#217 add repeated student report
	public function psRepeatedStudentsAction() {
	    $this->viewAssignEscaped ('locations', Location::getAll());
	
	    $helper = new Helper();
	    $this->view->assign('mode', 'id');
	    $this->view->assign('institutions', $helper->getInstitutions());
	    $this->view->assign('cadres', $helper->getCadres());
	    $this->view->assign('institutiontypes', $helper->AdminInstitutionTypes());
	    $this->view->assign('cohorts', $helper->getCohorts());
	    $this->view->assign('nationalities', $helper->getNationalities());
	    $this->view->assign('funding', $helper->getFunding());
	    $this->view->assign('tutors', $helper->getTutors());
	    $this->view->assign('facilities', $helper->getFacilities());
	    $this->view->assign('coursetypes', $helper->AdminCourseTypes());
	    $this->view->assign('degrees', $helper->getDegrees());
	    $this->view->assign('site_style', $this->setting('site_style'));
        $this->view->assign('termination_statuses', array('1' => t('Any Status'), '2' => t('Only Early Termination')));

	    if ($this->getSanParam('process')) {
	        $criteria = $this->getAllParams();
            // these two variables may be brought over from a different student report when the
            // report is changed.
            unset($criteria['showterminated']);
            unset($criteria['termination_status']);
            
	        if (isset($criteria['cohort']) && $criteria['cohort'] ||
	            isset($criteria['showcohort']) && $criteria['showcohort']) {
	
	                //TA:#217 add to $criteria addition param 'show_current_cohort'
	                $criteria['show_current_cohort'] = '1';
	                list($query1, $headers) = $this->psStudentReportsBuildQuery($criteria);
	                 
	                //TA:#217 add to $criteria addition param 'show_old_cohorts'
	                $criteria['show_current_cohort'] = '0';
	                $criteria['show_old_cohorts'] = '1';
	                list($query2, $headers) = $this->psStudentReportsBuildQuery($criteria);
	                $headers[] = "Current Cohorts";
	                 
	                //TA:#217 create query, take only repeated students (who dropped one cohort and joined to another)
// 	                $query = "select t1.*, t2.old_cohortname from (" . $query1 . ") as t1 ".
// 	                    "left join (" . $query2 . ") as t2 on t1.id=t2.id where t2.old_cohortname is not null";
	                //TA:#405 change columns output to take drop reason from t2
                    $query = "select t2.*, t1.current_cohortname from (" . $query1 . ") as t1 ".
 	                    "left join (" . $query2 . ") as t2 on t1.id=t2.id where t2.old_cohortname is not null";
	            }else{
	                list($query, $headers) = $this->psStudentReportsBuildQuery($criteria);
	                //take only repeated students (who dropped one cohort and joined to another)
	                //TA:#405 group by of the end of whole query
	                $query = $query . " and s.id in (select id_student from link_student_cohort where dropdate != '0000-00-00' 
	                    and id_student in (SELECT id_student FROM link_student_cohort group by id_student having count(*) > 1)) 
	                    GROUP BY p.id";
	            }

	            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	          // print $query;
	            $rowArray = $db->fetchAll($query);
	            $this->viewAssignEscaped("headers", $headers);
	            $this->viewAssignEscaped("output", $rowArray);
	
	            $this->view->assign('criteria', $criteria);
	    }
	}


	public function psStudentsByNameAction() {
		$this->viewAssignEscaped ('locations', Location::getAll());

		$helper = new Helper();
		$this->view->assign('mode', 'id');
		$this->view->assign('institutions', $helper->getInstitutions(false));//TA:#460
		$this->view->assign('cadres', $helper->getCadres());
		$this->view->assign('institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign('cohorts', $helper->getCohorts());
		$this->view->assign('nationalities', $helper->getNationalities());
		$this->view->assign('funding', $helper->getFunding());
		$this->view->assign('tutors', $helper->getTutors());
		$this->view->assign('facilities', $helper->getFacilities());
		$this->view->assign('coursetypes', $helper->AdminCourseTypes());
		$this->view->assign('degrees', $helper->getDegrees());
		$this->view->assign('site_style', $this->setting('site_style'));
        //TA:#334 $this->view->assign('termination_statuses', array('1' => t('Any Status'), '2' => t('Only Early Termination')));

		if ($this->getSanParam('process')) {
			$criteria = $this->getAllParams();

			list($query, $headers) = $this->psStudentReportsBuildQuery($criteria);

			$db = Zend_Db_Table_Abstract::getDefaultAdapter();
			$rowArray = $db->fetchAll($query);
			//print $query;
			$this->view->assign('query', $query->__toString());

			$this->viewAssignEscaped("headers", $headers);
			$this->viewAssignEscaped("output", $rowArray);

			$this->view->assign('criteria', $criteria);
		}
	}
	
	//TA:#496
	public function psStudentsAddressAction() {
	    $this->viewAssignEscaped ('locations', Location::getAll());
	
	    $helper = new Helper();
	    $this->view->assign('mode', 'id');
	    $this->view->assign('institutions', $helper->getInstitutions());
	    $this->view->assign('cadres', $helper->getCadres());
	    $this->view->assign('institutiontypes', $helper->AdminInstitutionTypes());
	    $this->view->assign('cohorts', $helper->getCohorts());
	    $this->view->assign('relationships', $helper->getRelationship()); //TA:#504
// 	    $this->view->assign('nationalities', $helper->getNationalities());
// 	    $this->view->assign('funding', $helper->getFunding());
// 	    $this->view->assign('tutors', $helper->getTutors());
// 	    $this->view->assign('facilities', $helper->getFacilities());
// 	    $this->view->assign('coursetypes', $helper->AdminCourseTypes());
// 	    $this->view->assign('degrees', $helper->getDegrees());
// 	    $this->view->assign('site_style', $this->setting('site_style'));
// 	    //TA:#334 $this->view->assign('termination_statuses', array('1' => t('Any Status'), '2' => t('Only Early Termination')));
	
	    if ($this->getSanParam('process')) {
	        $criteria = $this->getAllParams();
	
	        list($query, $headers) = $this->psStudentReportsBuildQuery($criteria);
	
	        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	        $rowArray = $db->fetchAll($query);
	        //print $query;
	        $this->view->assign('query', $query->__toString());
	
	        $this->viewAssignEscaped("headers", $headers);
	        $this->viewAssignEscaped("output", $rowArray);
	
	        $this->view->assign('criteria', $criteria);
	    }
	}
	
	//TA:#486
	public function psStudentsLicensingAction() {
	
	    $helper = new Helper();
	    $this->view->assign('mode', 'id');
	    $this->view->assign('institutions', $helper->getInstitutions());
	    $this->view->assign('cadres', $helper->getCadres());
	    $this->view->assign('institutiontypes', $helper->AdminInstitutionTypes());
	    $this->view->assign('cohorts', $helper->getCohorts());
	    $this->view->assign('licenses', $helper->getLicenses());
	    
	    if ($this->getSanParam('process')) {
	        $criteria = $this->getAllParams();
	
	        list($query, $headers) = $this->psStudentReportsBuildQuery($criteria);
	
	        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
	        $rowArray = $db->fetchAll($query);
	        //print $query;
	        $this->view->assign('query', $query->__toString());
	
	        $this->viewAssignEscaped("headers", $headers);
	        $this->viewAssignEscaped("output", $rowArray);
	
	        $this->view->assign('criteria', $criteria);
	    }
	}
	

	public function psGraduatedStudentsAction() {
		//locations
		$this->viewAssignEscaped ( 'locations', Location::getAll () );

		$helper = new Helper();
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'institutions', $helper->getInstitutions());
		$this->view->assign ( 'cadres', $helper->getCadres());
		$this->view->assign ( 'institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign ( 'cohorts', $helper->getCohorts());
		$this->view->assign ( 'nationalities', $helper->getNationalities());
		$this->view->assign ( 'funding', $helper->getFunding());
		$this->view->assign ( 'tutors', $helper->getTutors());
		$this->view->assign ( 'facilities', $helper->getFacilities());
		$this->view->assign ( 'coursetypes', $helper->AdminCourseTypes());
		$this->view->assign ( 'degrees', $helper->getDegrees());
		$this->view->assign('site_style', $this->setting('site_style'));
        //TA:#334 $this->view->assign('termination_statuses', array('1' => t('Any Status'), '2' => t('Only Early Termination')));

		if ($this->getSanParam ('process')) {
			$criteria = $this->getAllParams();

			// these two variables may be brought over from a different student report when the
            // report is changed.
			unset($criteria['showterminated']);
			unset($criteria['termination_status']);

			list($query, $headers) = $this->psStudentReportsBuildQuery($criteria);
			$db = Zend_Db_Table_Abstract::getDefaultAdapter();
			$rowArray = $db->fetchAll($query);
			$this->view->assign('query', $query->__toString());

			$this->viewAssignEscaped("headers", $headers);
			$this->viewAssignEscaped("output", $rowArray);

			$this->view->assign('criteria', $criteria);
		}
	}

	public function psCourseByStudentCountAction() {
		//locations
		$this->viewAssignEscaped ( 'locations', Location::getAll () );

		$helper = new Helper();
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'institutions', $helper->getInstitutions());
		$this->view->assign ( 'cadres', $helper->getCadres());
		$this->view->assign ( 'institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign ( 'cohorts', $helper->getCohorts());
		$this->view->assign ( 'nationalities', $helper->getNationalities());
		$this->view->assign ( 'funding', $helper->getFunding());
		$this->view->assign ( 'tutors', $helper->getTutors());
		$this->view->assign ( 'facilities', $helper->getFacilities());
		$this->view->assign ( 'coursetypes', $helper->AdminCourseTypes());
		$this->view->assign ( 'degrees', $helper->getDegrees());

		#		return $this->trainingReport ();
		if ($this->getSanParam ( 'process' )){

			$maintable = "classes class";
			$select = array();
			//$select[] = "class.id";
			$select[] = "class.classname";
			$select[] = "(SELECT COUNT(*) FROM link_student_classes WHERE classid = class.id) AS student_count";

			$headers[] = "Class Name";
			$headers[] = "Student Count";

			$institution_set = false;

			$where = array();
			$join = array();
			$sort = array();
			$locations = Location::getAll ();
			$translation = Translation::getAll ();

			// institution
			if ($this->getSanParam ( 'showinstitution' )){
				$select[] = "i.institutionname";
				$headers[] = "Institution";

				$join[] = array(
					"table" => "link_cohorts_classes",
					"abbreviation" => "lcc",
					"compare" => "lcc.cohortid = class.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "cohort",
					"abbreviation" => "coh",
					"compare" => "coh.id = lcc.cohortid",
					"type" => "left"
				);
				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = coh.institutionid",
					"type" => "left"
				);
				$institution_set = true;

				if ($this->getSanParam('institution')){
					$where[] = "i.id = " . $this->getSanParam('institution');
				}
			}

			// cadre
			if ($this->getSanParam ('showcadre')){
				$select[] = "cad.cadrename";
				$headers[] = "Cadre";

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.cohortid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}

				$join[] = array(
					"table" => "link_cadre_institution",
					"abbreviation" => "cai",
					"compare" => "cai.id_institution = i.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "cadres",
					"abbreviation" => "cad",
					"compare" => "cad.id = cai.id_cadre",
					"type" => "left"
				);

				if ($this->getSanParam('cadre')){
					$where[] = "cad.id = " . $this->getSanParam('cadre');
				}
			}

			// cohort
			if ($this->getSanParam ( 'showcohort' )){
				$select[] = "coh.cohortname";
				$headers[] = "Cohort";

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.cohortid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}

				if ($this->getSanParam('cohort')){
					$where[] = "coh.id = " . $this->getSanParam('cohort');
				}
			}

			// year in school
			if ($this->getSanParam ( 'showyearinschool' )){
				# REQUIRES COHORT LINK
				$found = false;
				foreach ($join as $j){
					if ($j['table'] == "cohort"){
					$found = true;
					}
				}

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.cohortid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}
				$select[] = "coh.startdate";
				$headers[] = "Start Date";
				if ($this->getSanParam('yearinschool')){
					$db = Zend_Db_Table_Abstract::getDefaultAdapter();
					$where[] = $db->quoteInto("coh.startdate LIKE ", substr($this->getSanParam('yearinschool'), 0, 4) . '%');
				}
			}

			if ( $this->getSanParam('showcoursename') ){
				$select[] = "class.classname";
				$headers[] = "Course Name";
			}
			if( $this->getSanParam('coursename') ){
				$course_name = $this->getSanParam('coursename');
				$where[] = "classname LIKE '%{$this->getSanParam('coursename')}%'";
			}

			// course/classes type
			if( $this->getSanParam('showcoursetype') || $this->getSanParam('coursetype') ){
				$join[] = array(
					"table" => "lookup_coursetype",
					"abbreviation" => "ctype",
					"compare" => "ctype.id = class.coursetypeid",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showcoursetype') ){
				$select[] = "ctype.coursetype";
				$headers[] = "Course Type";
			}
			if( $this->getSanParam('coursetype') ){
				$where[] = "ctype.id = ".$this->getSanParam('coursetype');
			}

			// start date between
			$start_date = '';
			if($this->getSanParam('startday') && $this->getSanParam('startmonth') && $this->getSanParam('startyear')){
				$start_date = $this->getSanParam('startyear').'-'.$this->getSanParam('startmonth').'-'.$this->getSanParam('startday');
			}
			$end_date = '';
			if($this->getSanParam('endday') && $this->getSanParam('endmonth') && $this->getSanParam('endyear')){
				$end_date = $this->getSanParam('endyear').'-'.$this->getSanParam('endmonth').'-'.$this->getSanParam('endday');
			}
			if(($start_date != '') || ($end_date != '')){
				$select[] = "c.startdate";
				$headers[] = "Start Date";

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.cohortid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}

			}
			if(($start_date != '') && ($end_date != '')){
				$where[] = "coh.startdate BETWEEN '{$start_date}' AND '{$end_date}'";
			} else {
				if ($start_date != ''){
					$where[] = "coh.startdate >= '{$start_date}'";
				}
				if ($end_date != ''){
					$where[] = "coh.startdate <= '{$end_date}'";
				}
			}

			/*
			if( $this->getSanParam('showgrades') || $this->getSanParam('grades') ){
				///////
			}

			// grades
			if( $this->getSanParam('showgrades') ){
				$select[] = "lsclass.grade";
				$headers[] = "Grade";
			}
			if( $this->getSanParam('grades') ){
				$grade = $this->getSanParam('grades');
				$where[] = "lsclass.grade LIKE '%{$grade}%'";
			}
			*/

			// topic
			if( $this->getSanParam('showtopic') ){
				$select[] = "class.coursetopic";
				$headers[] = "Topic";
			}
			if( $this->getSanParam('topic') ){
				$topic = $this->getSanParam('topic');
				$where[] = "class.coursetopic LIKE '%{$topic}%'";
			}

			// filter by user institution
			if(!$institution_set){
				$join[] = array(
					"table" => "link_cohorts_classes",
					"abbreviation" => "lcc",
					"compare" => "lcc.cohortid = class.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "cohort",
					"abbreviation" => "coh",
					"compare" => "coh.id = lcc.cohortid",
					"type" => "left"
				);
				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = coh.institutionid",
					"type" => "left"
				);
				$institution_set = true;
			}
			$login_user_id = $helper->myid();
			$ins_results = $helper->getUserInstitutions($login_user_id);
			if( !empty($ins_results) ){
				$where[] = "i.id IN (SELECT institutionid FROM link_user_institution WHERE userid = {$login_user_id})";
			}

			$query = "SELECT " . implode(", ", $select) . "\n";
			$query .= " FROM " . $maintable . "\n";
			if (count ($join) > 0){
				foreach ($join as $j){
					$query .= strtoupper($j['type']) . " JOIN " . $j['table'] . " " . $j['abbreviation'] . " ON " . $j['compare'] . "\n";
				}
			}
			if (count ($where) > 0){
				$query .= "WHERE " . implode(" AND ", $where) . "\n";
			}
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$rowArray = $db->fetchAll ($query);
			$this->view->assign('output',$rowArray);
			$this->view->assign('query',$query);

			//echo $query;
			# exit;

			$this->viewAssignEscaped("headers", $headers);
			$this->view->assign('output', $rowArray);
			$this->view->assign('query', "");

			$this->view->criteria = $_GET;
		}
	}

	public function psCourseByNameAction() {
		//locations
		$this->viewAssignEscaped ( 'locations', Location::getAll () );

		$helper = new Helper();
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'institutions', $helper->getInstitutions());
		$this->view->assign ( 'cadres', $helper->getCadres());
		$this->view->assign ( 'institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign ( 'cohorts', $helper->getCohorts());
		$this->view->assign ( 'nationalities', $helper->getNationalities());
		$this->view->assign ( 'funding', $helper->getFunding());
		$this->view->assign ( 'tutors', $helper->getTutors());
		$this->view->assign ( 'facilities', $helper->getFacilities());
		$this->view->assign ( 'coursetypes', $helper->AdminCourseTypes());
		$this->view->assign ( 'degrees', $helper->getDegrees());
		$this->view->assign ( 'lookuplanguages', $helper->getLanguages());

		#		return $this->trainingReport ();
		if ($this->getSanParam ( 'process' )){

			$maintable = "classes class";
			$select = array();
			//$select[] = "class.id";
			$select[] = "class.classname";

			$headers[] = "Class Name";

			$institution_set = false;

			$where = array();
			$join = array();
			$sort = array();
			$locations = Location::getAll ();
			$translation = Translation::getAll ();

			// institution
			if ($this->getSanParam ( 'showinstitution' )){
				$select[] = "i.institutionname";
				$headers[] = "Institution";

				$join[] = array(
					"table" => "link_cohorts_classes",
					"abbreviation" => "lcc",
					"compare" => "lcc.classid = class.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "cohort",
					"abbreviation" => "coh",
					"compare" => "coh.id = lcc.cohortid",
					"type" => "left"
				);
				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = coh.institutionid",
					"type" => "left"
				);
				$institution_set = true;

				if ($this->getSanParam('institution')){
					$where[] = "i.id = " . $this->getSanParam('institution');
				}
			}

			// cadre
			if ($this->getSanParam ('showcadre')){
				$select[] = "cad.cadrename";
				$headers[] = "Cadre";

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.classid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}

				$join[] = array(
					"table" => "link_cadre_institution",
					"abbreviation" => "cai",
					"compare" => "cai.id_institution = i.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "cadres",
					"abbreviation" => "cad",
					"compare" => "cad.id = cai.id_cadre",
					"type" => "left"
				);

				if ($this->getSanParam('cadre')){
					$where[] = "cad.id = " . $this->getSanParam('cadre');
				}
			}

			// cohort
			if ($this->getSanParam ( 'showcohort' )){
				$select[] = "coh.cohortname";
				$headers[] = "Cohort";

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.classid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}

				if ($this->getSanParam('cohort')){
					$where[] = "coh.id = " . $this->getSanParam('cohort');
				}
			}

			// year in school
			if ($this->getSanParam ( 'showyearinschool' )){
				# REQUIRES COHORT LINK
				$found = false;
				foreach ($join as $j){
					if ($j['table'] == "cohort"){
					$found = true;
					}
				}

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.classid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}
				$select[] = "coh.startdate";
				$headers[] = "Start Date";
				if ($this->getSanParam('yearinschool')){
					$db = Zend_Db_Table_Abstract::getDefaultAdapter();
					$where[] = $db->quoteInto("coh.startdate LIKE ", substr($this->getSanParam('yearinschool'), 0, 4)) . '%';
				}
			}

			if( $this->getSanParam('coursename') ){
				$course_name = $this->getSanParam('coursename');
				$where[] = "class.classname LIKE '%{$course_name}%'";
			}

			// course/classes type
			if( $this->getSanParam('showcoursetype') || $this->getSanParam('coursetype') ){
				$join[] = array(
					"table" => "lookup_coursetype",
					"abbreviation" => "ctype",
					"compare" => "ctype.id = class.coursetypeid",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showcoursetype') ){
				$select[] = "ctype.coursetype";
				$headers[] = "Course Type";
			}
			if( $this->getSanParam('coursetype') ){
				$where[] = "ctype.id = ".$this->getSanParam('coursetype');
			}

			// topic
			if( $this->getSanParam('showtopic') ){
				$select[] = "tto.training_topic_phrase";
				$headers[] = "Topic";
				$join[] = array(
					"table" => "training_topic_option",
					"abbreviation" => "tto",
					"compare" => "tto.id = class.coursetopic",
					"type" => "left"
				);
			}
			if( $this->getSanParam('topic') ){
				$topic = $this->getSanParam('topic');
				$where[] = "class.coursetopic LIKE '%{$topic}%'";
			}

			// # of exams
			// ..

			// # of students
			if( $this->getSanParam('showstudentcount') || $this->getSanParam('studentcount') ){

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.classid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}
			}
			if( $this->getSanParam('showstudentcount') ){
				$select[] = "i.studentcount";
				$headers[] = "Student Count";
			}
			if( $this->getSanParam('studentcount') ){
				$where[] = "i.studentcount = ".$this->getSanParam('studentcount');

			}

			// start date between
			$start_date = '';
			if($this->getSanParam('startday') && $this->getSanParam('startmonth') && $this->getSanParam('startyear')){
				$start_date = $this->getSanParam('startyear').'-'.$this->getSanParam('startmonth').'-'.$this->getSanParam('startday');
			}
			$end_date = '';
			if($this->getSanParam('endday') && $this->getSanParam('endmonth') && $this->getSanParam('endyear')){
				$end_date = $this->getSanParam('endyear').'-'.$this->getSanParam('endmonth').'-'.$this->getSanParam('endday');
			}
			if(($start_date != '') || ($end_date != '')){
				$select[] = "coh.startdate";
				$headers[] = "Start Date";

				if(!$institution_set){
					$join[] = array(
						"table" => "link_cohorts_classes",
						"abbreviation" => "lcc",
						"compare" => "lcc.classid = class.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "cohort",
						"abbreviation" => "coh",
						"compare" => "coh.id = lcc.cohortid",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}
			}
			if(($start_date != '') && ($end_date != '')){
				$where[] = "coh.startdate BETWEEN '{$start_date}' AND '{$end_date}'";
			} else {
				if ($start_date != ''){
					$where[] = "coh.startdate >= '{$start_date}'";
				}
				if ($end_date != ''){
					$where[] = "coh.startdate <= '{$end_date}'";
				}
			}

			// filter by user institution
			if(!$institution_set){
				$join[] = array(
					"table" => "link_cohorts_classes",
					"abbreviation" => "lcc",
					"compare" => "lcc.classid = class.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "cohort",
					"abbreviation" => "coh",
					"compare" => "coh.id = lcc.cohortid",
					"type" => "left"
				);
				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = coh.institutionid",
					"type" => "left"
				);
				$institution_set = true;
			}
			$login_user_id = $helper->myid();
			$ins_results = $helper->getUserInstitutions($login_user_id);
			if( !empty($ins_results) ){
				$where[] = "i.id IN (SELECT institutionid FROM link_user_institution WHERE userid = {$login_user_id})";
			}

			$query = "SELECT " . implode(", ", $select) . "\n";
			$query .= " FROM " . $maintable . "\n";
			if (count ($join) > 0){
				foreach ($join as $j){
					$query .= strtoupper($j['type']) . " JOIN " . $j['table'] . " " . $j['abbreviation'] . " ON " . $j['compare'] . "\n";
				}
			}
			if (count ($where) > 0){
				$query .= "WHERE " . implode(" AND ", $where) . "\n";
			}
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$rowArray = $db->fetchAll ($query);
			$this->viewAssignEscaped("headers", $headers);
			$this->view->assign('output',$rowArray);
			$this->view->assign('query',$query);
			//echo $query;
			# exit;

			$this->view->criteria = $_GET;
		}
	}

	public function psCohortByParticipantCountAction() {
		//locations
		$this->viewAssignEscaped ( 'locations', Location::getAll () );

		$helper = new Helper();
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'institutions', $helper->getInstitutions());
		$this->view->assign ( 'cadres', $helper->getCadres());
		$this->view->assign ( 'institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign ( 'cohorts', $helper->getCohorts());
		$this->view->assign ( 'nationalities', $helper->getNationalities());
		$this->view->assign ( 'funding', $helper->getFunding());
		$this->view->assign ( 'tutors', $helper->getTutors());
		$this->view->assign ( 'facilities', $helper->getFacilities());
		$this->view->assign ( 'facilitytypes', $helper->getFacilityTypes());
		$this->view->assign ( 'sponsors', $helper->getSponsors());
		$this->view->assign ( 'coursetypes', $helper->AdminCourseTypes());
		$this->view->assign ( 'degrees', $helper->getDegrees());
		$this->view->assign ( 'lookuplanguages', $helper->getLanguages());

		if ($this->getSanParam ( 'process' )){

			$maintable = "cohort coh";
			$select = array();
			//$select[] = "coh.id AS cohort_id";
			$select[] = "coh.cohortname";

			$headers[] = t("Cohort Name");

			$institution_set = false;

			$join = array();
			$where = array();
			$sort = array();
			$locations = Location::getAll ();
			$translation = Translation::getAll ();

			$count_query_joins = '';
			$count_query_where = '';

			// institution
			if ($this->getSanParam ( 'showinstitution' )){
				$select[] = "i.institutionname";
				$headers[] = "Institution";

				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = coh.institutionid",
					"type" => "left"
				);
				$institution_set = true;

				if ($this->getSanParam('institution')){
					$where[] = "i.id = " . $this->getSanParam('institution');
				}
			}

			// cadre
			if ($this->getSanParam ('showcadre')){
				$select[] = "cad.cadrename";
				$headers[] = "Cadre";

				if(!$institution_set){
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}
//TA:94 fixing bugs because it displays all cadres of institution, but should display only one
// 				$join[] = array(
// 					"table" => "link_cadre_institution",
// 					"abbreviation" => "cai",
// 					"compare" => "cai.id_institution = i.id",
// 					"type" => "left"
// 				);
// 				$join[] = array(
// 					"table" => "cadres",
// 					"abbreviation" => "cad",
// 					"compare" => "cad.id = cai.id_cadre",
// 					"type" => "left"
// 				);
				$join[] = array(
				    "table" => "cadres",
				    "abbreviation" => "cad",
				    "compare" => "cad.id = coh.cadreid",
				    "type" => "left"
				);

				if ($this->getSanParam('cadre')){
					$where[] = "cad.id = " . $this->getSanParam('cadre');
				}
			}

			// degree
			if($this->getSanParam('showdegree') || $this->getSanParam('degree')){
				if(!$institution_set){
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = coh.institutionid",
						"type" => "left"
					);
					$institution_set = true;
				}

				$join[] = array(
					"table" => "link_institution_degrees",
					"abbreviation" => "liddeg",
					"compare" => "liddeg.id_institution = i.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_degrees",
					"abbreviation" => "ldeg",
					"compare" => "ldeg.id = liddeg.id_degree",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showdegree') ){
				$select[] = "ldeg.degree";
				$headers[] = "Degree";
			}
			if( $this->getSanParam('degree') ){
				$where[] = "ldeg.id = ".$this->getSanParam('degree');
			}

			// gender
			$count_query_joins .= " INNER JOIN student ON student.id = link_student_cohort.id_student INNER JOIN person ON person.id = student.personid ";
			if( $this->getSanParam('gender') ){
				$gender_id = $this->getSanParam('gender');
				if($gender_id > 0){
					$count_query_where .= " AND person.gender = '{$gender_arr[$gender_id]}'";
				}
			}

			// nationalities
			if( $this->getSanParam('nationality') ){
				$national_id = $this->getSanParam('nationality');
				$count_query_where .= " AND person.national_id = '{$national_id}'";
			}

			if($this->getSanParam('agemin') || $this->getSanParam('agemax')){
				$year_secs = 60 * 60 * 24 * 365;
				if($this->getSanParam('agemin') && $this->getSanParam('agemax')){
					$min_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemin') * $year_secs)));
					$max_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemax') * $year_secs)));
					$count_query_where .= " AND (person.birthdate BETWEEN '{$max_age_birthdate}' AND '{$min_age_birthdate}') ";
				} else {
					if ( $this->getSanParam('agemin') ){
						$min_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemin') * $year_secs)));
						$count_query_where .= " AND person.birthdate <= '{$min_age_birthdate}' ";
					}
					if ( $this->getSanParam('agemax') ){
						$max_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemax') * $year_secs)));
						$count_query_where .= " AND person.birthdate >= '{$max_age_birthdate}' ";
					}
				}
			}

			// graduation date
			if($this->getSanParam('showgraduationdate')){
				$select[] = "coh.graddate";
				$headers[] = "Graduation Date";
			}

			// Course Name and Exam Scores to Date
			// ..
			// TODO : how?

			// student names
			// ..
			// TODO : how?

			// active
			if( $this->getSanParam('showactive') ){
				$count_query_where .= " AND person.active = 'active' ";
			}

			/* TODO : how?
			// terminated early
			if( $this->getSanParam('showterminated') ){
				$select[] = "IF(lsc.isgraduated = 0 AND lsc.dropdate != '0000-00-00', 'Terminated Early', '')";
				$headers[] = "Terminated Early";

				$where[] = "lsc.isgraduated = 0";
				$where[] = "lsc.dropdate != '0000-00-00'";

				# REQUIRES COHORT LINK
				$found = false;
				foreach ($join as $j){ if ($j['table'] == "cohort"){ $found = true; } }
				if (!$found){
					$join[] = array("table" => "link_student_cohort", "abbreviation" => "lsc", "compare" => "lsc.id_student = s.id", "type" => "left");
					$join[] = array("table" => "cohort", "abbreviation" => "c", "compare" => "c.id = lsc.id_cohort", "type" => "left");
				}
			}
			*/

			// graduated
			if( $this->getSanParam('showgraduated') ){
				$where[] = "coh.graddate != '0000-00-00'";
			}

			/* TODO : how?
			// funding source
			if( $this->getSanParam('showfunding') ){
				$select[] = "lf.fundingname";
				$headers[] = "Funding";

				$join[] = array(
					"table" => "link_student_funding",
					"abbreviation" => "lsf",
					"compare" => "lsf.studentid = s.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_fundingsources",
					"abbreviation" => "lf",
					"compare" => "lf.id = lsf.fundingsource",
					"type" => "left"
				);
			}
			*/

			// student names
			if( $this->getSanParam('showstudentnames') ){
				$select[] = "CONCAT(p.first_name, ' ', p.last_name) AS student_name";
				$headers[] = "Student";
				$join[] = array(
					"table" => "link_student_cohort",
					"abbreviation" => "lscoh",
					"compare" => "lscoh.id_cohort = coh.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "student",
					"abbreviation" => "st",
					"compare" => "st.id = lscoh.id_student",
					"type" => "left"
				);
				$join[] = array(
					"table" => "person",
					"abbreviation" => "p",
					"compare" => "p.id = st.personid",
					"type" => "left"
				);
			}

			// start date between
			$start_date = '';
			if($this->getSanParam('startday') && $this->getSanParam('startmonth') && $this->getSanParam('startyear')){
				$start_date = $this->getSanParam('startyear').'-'.$this->getSanParam('startmonth').'-'.$this->getSanParam('startday');
			}
			$end_date = '';
			if($this->getSanParam('endday') && $this->getSanParam('endmonth') && $this->getSanParam('endyear')){
				$end_date = $this->getSanParam('endyear').'-'.$this->getSanParam('endmonth').'-'.$this->getSanParam('endday');
			}
			if(($start_date != '') || ($end_date != '')){
				$select[] = "coh.startdate";
				$headers[] = "Start Date";
			}
			if(($start_date != '') && ($end_date != '')){
				$where[] = "coh.startdate BETWEEN '{$start_date}' AND '{$end_date}'";
			} else {
				if ($start_date != ''){
					$where[] = "coh.startdate >= '{$start_date}'";
				}
				if ($end_date != ''){
					$where[] = "coh.startdate <= '{$end_date}'";
				}
			}

			// count query
			$select[] = "(SELECT COUNT(*) FROM link_student_cohort {$count_query_joins} WHERE id_cohort = coh.id {$count_query_where}) AS participate_count";
			$headers[] = "Participation";

			// filter by user institution
			if(!$institution_set){
				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = coh.institutionid",
					"type" => "left"
				);
				$institution_set = true;
			}
			$login_user_id = $helper->myid();
			$ins_results = $helper->getUserInstitutions($login_user_id);
			if( !empty($ins_results) ){
				$where[] = "i.id IN (SELECT institutionid FROM link_user_institution WHERE userid = {$login_user_id})";
			}

			$query = "SELECT " . implode(", ", $select) . "\n";
			$query .= " FROM " . $maintable . "\n";
			if (count ($join) > 0){
				foreach ($join as $j){
					$query .= strtoupper($j['type']) . " JOIN " . $j['table'] . " " . $j['abbreviation'] . " ON " . $j['compare'] . "\n";
				}
			}
			if (count ($where) > 0){
				$query .= "WHERE " . implode(" AND ", $where) . "\n";
			}

			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$rowArray = $db->fetchAll ($query);
			$this->viewAssignEscaped("headers", $headers);
			$this->view->assign('output',$rowArray);
			$this->view->assign('query',$query);
			//echo $query;
			# exit;

			$this->view->criteria = $_GET;
		}
	}



	public function psInstitutionInformationAction() {
		//locations
		$this->viewAssignEscaped ( 'locations', Location::getAll () );

		$helper = new Helper();
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'institutions', $helper->getInstitutions());
		$this->view->assign ( 'cadres', $helper->getCadres());
		$this->view->assign ( 'institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign ( 'cohorts', $helper->getCohorts());
		$this->view->assign ( 'nationalities', $helper->getNationalities());
		$this->view->assign ( 'funding', $helper->getFunding());
		$this->view->assign ( 'tutors', $helper->getTutors());
		$this->view->assign ( 'facilities', $helper->getFacilities());
		$this->view->assign ( 'facilitytypes', $helper->getFacilityTypes());
		$this->view->assign ( 'sponsors', $helper->getSponsors());
		$this->view->assign ( 'coursetypes', $helper->AdminCourseTypes());
		$this->view->assign ( 'degrees', $helper->getDegrees());
		$this->view->assign ( 'lookuplanguages', $helper->getLanguages());

		if ($this->getSanParam ( 'process' )){

			$maintable = "institution i";
			$select = array();
			//$select[] = "i.id";
			$select[] = "i.institutionname";

			$headers[] = "Institution";

			$join = array();
			$where = array();
			$sort = array();
			$locations = Location::getAll ();
			$translation = Translation::getAll ();

			// region
			if( $this->getSanParam('showProvince') || $this->getSanParam('province_id') || $this->getSanParam('showDistrict') || $this->getSanParam('district_id')){
				$join[] = array(
					"table" => "location",
					"abbreviation" => "loc",
					"compare" => "loc.id = i.geography1",
					"type" => "left"
				);
				$join[] = array(
					"table" => "location_district",
					"abbreviation" => "locd",
					"compare" => "locd.id = i.geography2",
					"type" => "left"
				);

				if( $this->getSanParam('showProvince') ){
					$select[] = "loc.location_name";
					$headers[] = "Province";
				}
				if( $this->getSanParam('showDistrict') ){
					$select[] = "locd.district_name";
					$headers[] = "District";
				}
			}
			$province_arr = $this->getSanParam('province_id');
			if( !empty($province_arr) ){
				$clause = ''; $or_str = '';
				foreach($province_arr as $item){
					$clause .= "{$or_str}loc.id = '{$item}'";
					$or_str = " OR ";
				}
				$clause = "({$clause})";
				$where[] = $clause;
			}
			$district_arr = $this->getSanParam('district_id');
			if( !empty($district_arr) ){
				$clause = ''; $or_str = '';
				foreach($district_arr as $item){
					$clause .= "{$or_str}locd.id = '{$item}'";
					$or_str = " OR ";
				}
				$clause = "({$clause})";
				$where[] = $clause;
			}

			// institution type
			if( $this->getSanParam('showinstitutiontype') || $this->getSanParam('institutiontype') || $this->getSanParam('showinstitutionsponsors') || $this->getSanParam('institutionsponsors') ){

				$join[] = array(
					"table" => "link_institution_institutiontype",
					"abbreviation" => "liit",
					"compare" => "liit.id_institution = i.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_institutiontype",
					"abbreviation" => "lit",
					"compare" => "lit.id = liit.id_institutiontype",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_sponsors",
					"abbreviation" => "ls",
					"compare" => "ls.id = i.sponsor",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showinstitutiontype') ){
				$select[] = "lit.typename";
				$headers[] = "Institution Type";
			}
			if( $this->getSanParam('institutiontype') ){
				$where[] = "lit.id = ".$this->getSanParam('institutiontype');
			}

			// institution sponsors
			if( $this->getSanParam('showinstitutionsponsors') ){
				$select[] = "ls.sponsorname";
				$headers[] = "Institution Sponsor";
			}
			if( $this->getSanParam('institutionsponsors') ){
				$where[] = "lit.id = ".$this->getSanParam('institutionsponsors');
			}

			// cadre
			if( $this->getSanParam('showcadre') ){
				$select[] = "cad.cadrename";
				$headers[] = "Cadre";

				$join[] = array(
					"table" => "link_cadre_institution",
					"abbreviation" => "cai",
					"compare" => "cai.id_institution = i.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "cadres",
					"abbreviation" => "cad",
					"compare" => "cad.id = cai.id_cadre",
					"type" => "left"
				);

				if( $this->getSanParam('cadre') ){
					$where[] = "cad.id = " . $this->getSanParam('cadre');
				}
			}

			// degree
			if($this->getSanParam('showdegree') || $this->getSanParam('degree')){
				$join[] = array(
					"table" => "link_institution_degrees",
					"abbreviation" => "liddeg",
					"compare" => "liddeg.id_institution = i.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_degrees",
					"abbreviation" => "ldeg",
					"compare" => "ldeg.id = liddeg.id_degree",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showdegree') ){
				$select[] = "ldeg.degree";
				$headers[] = "Degree";
			}
			if( $this->getSanParam('degree') ){
				$where[] = "ldeg.id = ".$this->getSanParam('degree');
			}

			// # of computers
			if( $this->getSanParam('showcomputercount') ){
				$select[] = "i.computercount";
				$headers[] = "Computer Count";
			}

			// # of Tutors
			if( $this->getSanParam('showtutorcount') ){
				$select[] = "i.tutorcount";
				$headers[] = "Tutor Count";
			}

			// # of Students
			if( $this->getSanParam('showstudentcount') ){
				$select[] = "i.studentcount";
				$headers[] = "Student Count";
			}

			// Tutor to Student Ratio
			if( $this->getSanParam('showratio') ){
				$select[] = "(i.tutorcount / i.studentcount) AS tutor_student_ratio";
				$headers[] = "Tutor Student Ratio";
			}

			// Dormitories
			if( $this->getSanParam('showdorms') ){
				$select[] = "i.dormcount";
				$headers[] = "Dorm Count";
			}

			// # of Beds
			if( $this->getSanParam('showbeds') ){
				$select[] = "i.bedcount";
				$headers[] = "Bed Count";
			}

			// filter by user institution
			$login_user_id = $helper->myid();
			$ins_results = $helper->getUserInstitutions($login_user_id);
			if( !empty($ins_results) ){
				$where[] = "i.id IN (SELECT institutionid FROM link_user_institution WHERE userid = {$login_user_id})";
			}

			$query = "SELECT " . implode(", ", $select) . "\n";
			$query .= " FROM " . $maintable . "\n";
			if (count ($join) > 0){
				foreach ($join as $j){
					$query .= strtoupper($j['type']) . " JOIN " . $j['table'] . " " . $j['abbreviation'] . " ON " . $j['compare'] . "\n";
				}
			}
			if (count ($where) > 0){
				$query .= "WHERE " . implode(" AND ", $where) . "\n";
			}
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$rowArray = $db->fetchAll ($query);
			$this->viewAssignEscaped("headers", $headers);
			$this->view->assign('output',$rowArray);
			$this->view->assign('query',$query);
			//echo $query;
			# exit;

			$this->view->criteria = $_GET;
		}
	}




	public function psTutorByNameAction() {
		//locations
		$this->viewAssignEscaped ( 'locations', Location::getAll () );

		$helper = new Helper();
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'institutions', $helper->getInstitutions());
		$this->view->assign ( 'cadres', $helper->getCadres());
		$this->view->assign ( 'institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign ( 'cohorts', $helper->getCohorts());
		$this->view->assign ( 'nationalities', $helper->getNationalities());
		$this->view->assign ( 'funding', $helper->getFunding());
		$this->view->assign ( 'tutors', $helper->getTutors());
		$this->view->assign ( 'facilities', $helper->getFacilities());
		$this->view->assign ( 'facilitytypes', $helper->getFacilityTypes());
		$this->view->assign ( 'sponsors', $helper->getSponsors());
		$this->view->assign ( 'coursetypes', $helper->AdminCourseTypes());
		$this->view->assign ( 'degrees', $helper->getDegrees());
		$this->view->assign ( 'tutortypes', $helper->AdminTutortypes());
		$this->view->assign ( 'lookuplanguages', $helper->getLanguages());

		if ($this->getSanParam ( 'process' )){

			$maintable = "tutor tut";
			$select = array();
			//$select[] = "tut.id";
			
			//TA:#390 to avoid names duplications only for fields with single values
			//for multiple display name duplications
			if( $this->getSanParam('showdegree') ){ 
			    $headers[] = "ID";
			    $select[] = "distinct(p.id)"; 
			}else{
			    $headers[] = "ID";
			    $select[] = "p.id";
			}
			
			$select[] = "p.first_name";
			$select[] = "p.last_name";

			$headers[] = "First Name";
			$headers[] = "Last Name";

			$join = array();
			$join[] = array(
				"table" => "person",
				"abbreviation" => "p",
				"compare" => "p.id = tut.personid",
				"type" => "inner"
			);

			$where = array();
			//TA:#254 only active tutors
			$where[] = "p.is_deleted = 0 and p.active = 'active' " ;
			$sort = array();
			$locations = Location::getAll ();
			$translation = Translation::getAll ();

			// region
			if( $this->getSanParam('showProvince') || $this->getSanParam('province_id') || $this->getSanParam('showDistrict') || $this->getSanParam('district_id')){

				$join[] = array(
					"table" => "link_tutor_institution",
					"abbreviation" => "lti",
					"compare" => "lti.id_tutor = tut.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = lti.id_institution",
					"type" => "left"
				);

				$join[] = array(
					"table" => "location",
					"abbreviation" => "loc",
					"compare" => "loc.id = i.geography1",
					"type" => "left"
				);
				$join[] = array(
					"table" => "location_district",
					"abbreviation" => "locd",
					"compare" => "locd.id = i.geography2",
					"type" => "left"
				);

				if( $this->getSanParam('showProvince') ){
					$select[] = "i.geography1";
					$headers[] = "Province";
				}
				if( $this->getSanParam('showDistrict') ){
					$select[] = "i.geography2";
					$headers[] = "District";
				}
			}
			$province_arr = $this->getSanParam('province_id');
			if( !empty($province_arr) ){
				$clause = ''; $or_str = '';
				foreach($province_arr as $item){
					$clause .= "{$or_str}loc.id = '{$item}'";
					$or_str = " OR ";
				}
				$clause = "({$clause})";
				$where[] = $clause;
			}
			$district_arr = $this->getSanParam('district_id');
			if( !empty($district_arr) ){
				$clause = ''; $or_str = '';
				foreach($district_arr as $item){
					$clause .= "{$or_str}locd.id = '{$item}'";
					$or_str = " OR ";
				}
				$clause = "({$clause})";
				$where[] = $clause;
			}

			// institution
			if ( $this->getSanParam('showinstitution') ){
				$select[] = "i.institutionname";
				$headers[] = "Institution";

				# REQUIRES INSTITUTION LINK
				$found = false;
				foreach ($join as $j){
					if ($j['table'] == "institution"){
						$found = true;
					}
				}
				if (!$found){
				    //TA:95 fix bug with institution for tutor (link_tutor_institution does not have info about all tutors)
// 					$join[] = array(
// 						"table" => "link_tutor_institution",
// 						"abbreviation" => "lti",
// 						"compare" => "lti.id_tutor = tut.id",
// 						"type" => "left"
// 					);
// 					$join[] = array(
// 						"table" => "institution",
// 						"abbreviation" => "i",
// 						"compare" => "i.id = lti.id_institution",
// 						"type" => "left"
// 					);
					$join[] = array(
					    "table" => "institution",
					    "abbreviation" => "i",
					    "compare" => "i.id = tut.institutionid",
					    "type" => "left"
					);
				}

				if ($this->getSanParam('institution')){
					$where[] = "i.id = " . $this->getSanParam('institution');
				}
			}

			// cadre
			if ($this->getSanParam ( 'showcadre' )){
				$select[] = "ca.cadrename";
				$headers[] = "Cadre";

				$join[] = array(
					"table" => "cadres",
					"abbreviation" => "ca",
					"compare" => "ca.id = tut.cadreid",
					"type" => "left"
				);

				if ($this->getSanParam('cadre')){
					$where[] = "ca.id = " . $this->getSanParam('cadre');
				}
			}

			// facility
			if( $this->getSanParam('showfacility') ){
				$select[] = "fac.facility_name";
				$headers[] = "Facility";
			}
			if( $this->getSanParam('facility') ){
				$where[] = "tut.facilityid = ".$this->getSanParam('facility');
			}
			if( $this->getSanParam('showfacility') || $this->getSanParam('facility') ){
				$join[] = array(
					"table" => "facility",
					"abbreviation" => "fac",
					"compare" => "fac.id = tut.facilityid",
					"type" => "left"
				);
			}

			// degree TA:#390
			if($this->getSanParam('showdegree') || $this->getSanParam('degree')){

				# REQUIRES INSTITUTION LINK
				$found = false;
				foreach ($join as $j){
					if ($j['table'] == "institution"){
						$found = true;
					}
				}
				if (!$found){
					$join[] = array(
						"table" => "link_tutor_institution",
						"abbreviation" => "lti",
						"compare" => "lti.id_tutor = tut.id",
						"type" => "left"
					);
					$join[] = array(
						"table" => "institution",
						"abbreviation" => "i",
						"compare" => "i.id = lti.id_institution",
						"type" => "left"
					);
				}

				$join[] = array(
					"table" => "link_institution_degrees",
					"abbreviation" => "liddeg",
					"compare" => "liddeg.id_institution = i.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_degrees",
					"abbreviation" => "ldeg",
					//"compare" => "ldeg.id = liddeg.id_degree", //TA:#390
				    "compare" => "ldeg.id = tut.degree",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showdegree') ){ //TA:#390
				$select[] = "ldeg.degree";
				$headers[] = "Degree";
			}
			if( $this->getSanParam('degree') ){ //TA:#390
				$where[] = "ldeg.id = ".$this->getSanParam('degree');
			}

			// degree institution
			if( $this->getSanParam('showdegreeinstitution') ){
				//$select[] = "tut.degreeinst";
				//TA:#420
				$join[] = array(
				    "table" => "lookup_degree_institution",
				    "abbreviation" => "ldi",
				    "compare" => "ldi.id = tut.degreeinst",
				    "type" => "left"
				);
				$select[] = "ldi.degree_institution as degreeinst";
				//
				$headers[] = "Degree Institution";
			}

			// degree year
			if( $this->getSanParam('showdegreeyear') ){
				$select[] = "tut.degreeyear";
				$headers[] = "Degree Year";
			}
			if( $this->getSanParam('degreeyear') ){
				$where[] = "tut.degreeyear = ".$this->getSanParam('degreeyear');
			}

			// tutor type
			if( $this->getSanParam('showtutortype') || $this->getSanParam('tutortype') ){
				$join[] = array(
					"table" => "link_tutor_tutortype",
					"abbreviation" => "ltutttype",
					"compare" => "ltutttype.id_tutor = tut.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_tutortype",
					"abbreviation" => "lttype",
					"compare" => "lttype.id = ltutttype.id_tutortype",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showtutortype') ){
				$select[] = "lttype.typename";
				$headers[] = "Tutor Type";
			}
			if( $this->getSanParam('tutortype') ){
				$where[] = "lttype.id = ".$this->getSanParam('tutortype');
			}

			// languages spoken
			if( $this->getSanParam('showtutortype') || $this->getSanParam('tutortype') ){
				$join[] = array(
					"table" => "link_tutor_languages",
					"abbreviation" => "ltutlang",
					"compare" => "ltutlang.id_tutor = tut.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "lookup_languages",
					"abbreviation" => "llang",
					"compare" => "llang.id = ltutlang.id_language",
					"type" => "left"
				);
			}
			if( $this->getSanParam('showlanguages') ){
				$select[] = "llang.language";
				$headers[] = "Language";
			}
			if( $this->getSanParam('languages') ){
				$where[] = "llang.id = ".$this->getSanParam('languages');
			}

			// # of students advised
			if( $this->getSanParam('showstudentsadvised') ){
			    //TA:109 fixed student advised results
// 				$select[] = "(SELECT COUNT(*) FROM student sub_s
// 									   INNER JOIN cadres sub_c ON sub_c.id = sub_s.cadre
// 									   INNER JOIN link_cadre_tutor sub_lct ON sub_lct.id_cadre = sub_c.id
// 									   INNER JOIN tutor sub_t ON sub_t.id = sub_lct.id_tutor
// 									   WHERE sub_t.id = tut.id) AS students_advised";

			    $select[] = "IFNULL(temp.students_advised, 0)";
			    $join[] = array(
			        "table" => "(select count(*) as students_advised, student.advisorid as adid from student join tutor on tutor.personid=student.advisorid group by advisorid)",
			        "abbreviation" => "temp",
			        "compare" => "temp.adid=p.id",
			        "type" => "left"
			    );
			    
				$headers[] = "Students Advised";
			}

			// tutor length
			if( $this->getSanParam('showtutorlength') ){
				$select[] = "(tut.tutortimehere - tut.tutorsince) AS tutor_length";
				$headers[] = "Tutor Length";
			}

			// length with current institution
			if( $this->getSanParam('showtutorcurlength') ){
				$select[] = "(tut.tutortimehere - tut.tutorsince) AS cur_tutor_length";
				$headers[] = "Tutor Current Length";
			}

			// gender
			if( $this->getSanParam('showgender') ){
				$select[] = "p.gender";
				$headers[] = "Gender";
			}

			// age
			if( $this->getSanParam('showage') ){
				$select[] = "DATE_FORMAT(FROM_DAYS(TO_DAYS(NOW())-TO_DAYS(p.birthdate)), '%Y')+0 AS age";
				$headers[] = "Age";
			}
			if($this->getSanParam('agemin') || $this->getSanParam('agemax')){
				$year_secs = 60 * 60 * 24 * 365;
				if($this->getSanParam('agemin') && $this->getSanParam('agemax')){
					$min_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemin') * $year_secs)));
					$max_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemax') * $year_secs)));
					$where[] = "p.birthdate BETWEEN '{$max_age_birthdate}' AND '{$min_age_birthdate}'";
				} else {
					if ( $this->getSanParam('agemin') ){
						$min_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemin') * $year_secs)));
						$where[] = "p.birthdate <= '{$min_age_birthdate}'";
					}
					if ( $this->getSanParam('agemax') ){
						$max_age_birthdate = date('Y-m-d', (time() - ($this->getSanParam('agemax') * $year_secs)));
						$where[] = "p.birthdate >= '{$max_age_birthdate}'";
					}
				}
			}

			if( $this->getSanParam('showemail') || $this->getSanParam('showphone') ){
				$join[] = array(
					"table" => "link_tutor_contacts",
					"abbreviation" => "ltutcon",
					"compare" => "ltutcon.id_tutor = tut.id",
					"type" => "left"
				);
			}

			// email
			if( $this->getSanParam('showemail') ){
				$join[] = array(
					"table" => "lookup_contacts",
					"abbreviation" => "lcon_email",
					"compare" => "(lcon_email.id = ltutcon.id_contact AND lcon_email.contactname = 'email')",
					"type" => "left"
				);
				$select[] = "ltutcon.contactvalue";
				$headers[] = "Email";
			}

			// phone
			if( $this->getSanParam('showphone') ){
				$join[] = array(
					"table" => "lookup_contacts",
					"abbreviation" => "lcon_phone",
					"compare" => "(lcon_phone.id = ltutcon.id_contact AND lcon_phone.contactname = 'phone')",
					"type" => "left"
				);
				$select[] = "ltutcon.contactvalue";
				$headers[] = "Phone";
			}

			// filter by user institution
			# REQUIRES INSTITUTION LINK
			$found = false;
			foreach ($join as $j){
				if ($j['table'] == "institution"){
					$found = true;
				}
			}
			if (!$found){
				$join[] = array(
					"table" => "link_tutor_institution",
					"abbreviation" => "lti",
					"compare" => "lti.id_tutor = tut.id",
					"type" => "left"
				);
				$join[] = array(
					"table" => "institution",
					"abbreviation" => "i",
					"compare" => "i.id = lti.id_institution",
					"type" => "left"
				);
			}
			$login_user_id = $helper->myid();
			$ins_results = $helper->getUserInstitutions($login_user_id);
			if( !empty($ins_results) ){
				$where[] = "i.id IN (SELECT institutionid FROM link_user_institution WHERE userid = {$login_user_id})";
			}

			$query = "SELECT " . implode(", ", $select) . "\n";
			$query .= " FROM " . $maintable . "\n";
			if (count ($join) > 0){
				foreach ($join as $j){
					$query .= strtoupper($j['type']) . " JOIN " . $j['table'] . " " . $j['abbreviation'] . " ON " . $j['compare'] . "\n";
				}
			}
			
			if (count ($where) > 0){
				$query .= "WHERE " . implode(" AND ", $where) . "\n";
			}
			
		
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$rowArray = $db->fetchAll ($query);
			$this->viewAssignEscaped("headers", $headers);
			$this->view->assign('output',$rowArray);
			$this->view->assign('query',$query);
			# exit;

			$this->view->criteria = $_GET;
		}
	}



	public function psFacilityReportAction() {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		//locations
		$this->viewAssignEscaped ( 'locations', Location::getAll () );

		$helper = new Helper();
		$this->view->assign ( 'mode', 'id' );
		$this->view->assign ( 'institutions', $helper->getInstitutions());
		$this->view->assign ( 'cadres', $helper->getCadres());
		$this->view->assign ( 'institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign ( 'cohorts', $helper->getCohorts());
		$this->view->assign ( 'nationalities', $helper->getNationalities());
		$this->view->assign ( 'funding', $helper->getFunding());
		$this->view->assign ( 'tutors', $helper->getTutors());
		$this->view->assign ( 'facilities', $helper->getFacilities());
		$this->view->assign ( 'facilitytypes', $helper->getFacilityTypes());
		$this->view->assign ( 'sponsors', $helper->getOldSponsors());
		$this->view->assign ( 'coursetypes', $helper->AdminCourseTypes());
		$this->view->assign ( 'degrees', $helper->getDegrees());
		$this->view->assign ( 'tutortypes', $helper->AdminTutortypes());

		if ($this->getSanParam ( 'process' )){
			// INITIALIZING ARRAYS
			$headers = array();
			$select = array();
			$from = array();
			$join = array();
			//
			//	$join[] = array(
			// 		"type"   => "inner",
			//		"table"  => "tablename t",
			//		"field1" => "t.field1",
			//		"field2" => "t2.field2",
			//	);
			//
			$where = array();
			$sort = array();

			$locations = Location::getAll ();
			$translation = Translation::getAll ();

			$showfacility 				= isset($_GET['showfacility']);
			$showProvince 				= isset($_GET['showProvince']);
			$showDistrict 				= isset($_GET['showDistrict']);
			$showRegionC 				= isset($_GET['showRegionC']);
			$showfacilitytype 			= isset($_GET['showfacilitytype']);
			$showinstitutionsponsors	= isset($_GET['showinstitutionsponsors']);
			$showcadre					= isset($_GET['showcadre']);
			$showgraduates				= isset($_GET['showgraduates']);
			$showgraduatesyear			= isset($_GET['showgraduatesyear']);
			$showpatients				= isset($_GET['showpatients']);
			$startday					= isset($_GET['startday']);

			$facility 					= $this->getSanParam('facility');
			$province_id 				= $this->getSanParam('province_id');
			$district_id 				= $this->getSanParam('district_id');
			$region_c_id 				= $this->getSanParam('region_c_id');
			$facilitytype 				= $this->getSanParam('facilitytype');
			$institutionsponsors 		= $this->getSanParam('institutionsponsors');
			$cadre 						= $this->getSanParam('cadre');

			$from[] = "facility f";
			//if ($showfacility){
				$headers[] = "Facility";
				$select[] = "f.facility_name";
				$sort[] = "f.facility_name";
			//}

			if ($facility != ""){
				$where[] = "f.id = " . $facility;
			}

			if ($showfacilitytype || $facilitytype){
				// Need join on facility type to show OR filter
				if ($showfacilitytype){
					// Only add header and select if showing field
					$headers[] = "Facility type";
					$select[] = "fto.facility_type_phrase";
				}

				$join[] = array(
					"type"		=> "inner",
					"table"		=> "facility_type_option fto",
					"field1"	=> "fto.id",
					"field2"	=> "f.type_option_id",
				);
				if ($facilitytype){
					$where[] = "fto.id = " . $facilitytype;
				}
				$sort[] = "fto.facility_type_phrase";
			}

			if ($showinstitutionsponsors || $institutionsponsors){
				// Need join on facility type to show OR filter
				if ($showinstitutionsponsors){
					// Only add header and select if showing field
					$headers[] = "Sponsor";
					$select[] = "fso.facility_sponsor_phrase";
				}

				// OPTIONAL LINK - LEFT JOINING
				$join[] = array(
					"type"		=> "left",
					"table"		=> "facility_sponsor_option fso",
					"field1"	=> "fso.id",
					"field2"	=> "f.sponsor_option_id",
				);
				if ($institutionsponsors){
					$where[] = "fso.id = " . $institutionsponsors;
				}
				$sort[] = "fso.facility_sponsor_phrase";
			}

			// INCLUDING LOCATION IDENTIFYER, IF NECESSARY
			if (($region_c_id != "") || ($district_id != "") || ($province_id != "") || ($showProvince != "") || ($showDistrict != "") || ($showRegionC != "")){
				$select[] = "f.location_id";
			}

			if ($showcadre || $cadre){
				$join[] = array(
					"type"		=> "left",
					"table"		=> "person_qualification_option pqo",
					"field1"	=> "pqo.id",
					"field2"	=> "f.sponsor_option_id",
				);
			}

			$query = "SELECT ";
			$query .= implode (", ", $select);
			$query .= " FROM ";
			$query .= implode (", ", $from) . " ";
			if (count ($join) > 0){
				foreach ($join as $j){
					$query .= strtoupper($j['type']) . " JOIN " . $j['table'] . " ON " . $j['field1'] . " = " . $j['field2'] . " ";
				}
			}
			if (count ($where) > 0){
				$query .= " WHERE " . implode(" AND ", $where);
			}

			if (count ($sort) > 0){
				$query .= " ORDER BY " . implode(", ", $sort);
			}



			//echo $query . "<br>";
			$rows = $db->fetchAll ($query);
			$regions = array();


#			var_dump ($rows);
			// Filtering by locations
			if (($region_c_id != "") || ($district_id != "") || ($province_id != "")){
				$__rows = array();
				if ($region_c_id != ""){
					// 3 levels selected. Going with this one first
					$regions = explode("_", $region_c_id[0]);
				} elseif ($district_id != ""){
					// 2 levels selected
					$regions = explode("_", $district_id[0]);
				} elseif ($province_id != ""){
					// 1 level selected
					$regions = explode("_", $province_id[0]);
				}

				// Include headers once
				if ($showProvince){
					$headers[] = @$translation ['Region A (Province)'];
				}
				if ($showDistrict){
					$headers[] = @$translation ['Region B (Health District)'];
				}
				if ($showRegionC){
					$headers[] = @$translation ['Region C (Local Region)'];
				}

				foreach ($rows as $row){
					list ( $cname, $prov, $dist, $regc ) = Location::getCityInfo ( $row['location_id'], $this->setting ( 'num_location_tiers' ) );
					if ($showProvince){
						$loc = $locations[$prov];
						$row[@$translation ['Region A (Province)']] = $loc['name'];
					}
					if ($showDistrict){
						$loc = $locations[$dist];
						$row[@$translation ['Region B (Health District)']] = $loc['name'];
					}
					if ($showRegionC){
						$loc = $locations[$regc];
						$row[@$translation ['Region C (Local Region)']] = $loc['name'];
					}

					unset ($row['location_id']);

					$userow = true;
					if (count ($regions) > 0){
						switch (count ($regions)){
							case 1:
								// Selected province
								if ($prov != $regions[0]){
									$userow = false;
								}
							break;
							case 2:
								// Selected province, district
								if (($prov != $regions[0]) || ($dist != $regions[1])){
									$userow = false;
								}
							break;
							case 3:
								// Selected province, district, regionc
								if (($prov != $regions[0]) || ($dist != $regions[1]) || ($regc != $regions[2])){
									$userow = false;
								}
							break;
						}
					}

					if ($userow){
						$__rows[] = $row;
					}
				}
				$rows = $__rows;
			} elseif (($showProvince != "") || ($showDistrict != "") || ($showRegionC != "")){
				// NOT FILTERING, BUT STILL INCLUDING LOCATION COLUMNS
				// Include headers once
				if ($showProvince){
					$headers[] = @$translation ['Region A (Province)'];
				}
				if ($showDistrict){
					$headers[] = @$translation ['Region B (Health District)'];
				}
				if ($showRegionC){
					$headers[] = @$translation ['Region C (Local Region)'];
				}
				$__rows = array();
				foreach ($rows as $row){
					list ( $cname, $prov, $dist, $regc ) = Location::getCityInfo ( $row['location_id'], $this->setting ( 'num_location_tiers' ) );
					if ($showProvince){
						$loc = $locations[$prov];
						$row[@$translation ['Region A (Province)']] = $loc['name'];
					}
					if ($showDistrict){
						$loc = $locations[$dist];
						$row[@$translation ['Region B (Health District)']] = $loc['name'];
					}
					if ($showRegionC){
						$loc = $locations[$regc];
						$row[@$translation ['Region C (Local Region)']] = $loc['name'];
					}
					unset ($row['location_id']);
					$__rows[] = $row;
				}
				$rows = $__rows;
			}

			$this->viewAssignEscaped("headers", $headers);
			$this->viewAssignEscaped("output", $rows);
		}
	}


	/***************************************************************
	 *                                                             *
	 *    #### #   # # #     #      #### #   #   #   ####  #####   *
	 *   #     #  #  # #     #     #     ## ##  # #  #   #   #     *
	 *   #     # #   # #     #     #     # # #  # #  #   #   #     *
	 *    ###  ##    # #     #      ###  # # # ##### ####    #     *
	 *       # # #   # #     #         # #   # #   # #   #   #     *
	 *       # #  #  # #     #         # #   # #   # #   #   #     *
	 *   ####  #   # # ##### ##### ####  #   # #   # #   #   #     *
	 *                                                             *
	 ***************************************************************/


	public function ssChwStatementOfResultsAction() {
		if (!$this->hasACL('view_people') and !$this->hasACL('edit_people')) {
			$this->doNoAccessError ();
		}
		$this->viewAssignEscaped ('locations', Location::getAll());
		$helper = new Helper();
		$this->view->assign('mode', 'id');
		$this->view->assign('institutions', $helper->getInstitutions());
		$this->view->assign('cadres', $helper->getCadres());
		$this->view->assign('institutiontypes', $helper->AdminInstitutionTypes());
		$this->view->assign('cohorts', $helper->getCohorts());
		$this->view->assign('nationalities', $helper->getNationalities());
		$this->view->assign('funding', $helper->getFunding());
		$this->view->assign('tutors', $helper->getTutors());
		$this->view->assign('facilities', $helper->getFacilities());
		$this->view->assign('coursetypes', $helper->AdminCourseTypes());
		$this->view->assign('degrees', $helper->getDegrees());
		$this->view->assign('site_style', $this->setting('site_style'));
        $this->view->assign('termination_statuses', array('1' => t('Any Status'), '2' => t('Only Early Termination')));

        $criteria = array();
        $criteria['showinstitution'] = true;
        $criteria['showcadre'] = true;
        $criteria['showcohort'] = true;

		if ($this->getSanParam('process')) {
			$criteria = $this->getAllParams();

			$criteria['showinstitution'] = true;
			$criteria['showcadre'] = true;
			$criteria['showcohort'] = true;

			list($query, $headers) = $this->psStudentReportsBuildQuery($criteria);

			$query->joinLeft(array('ci' => 'certificate_issuers'), 'lsc.certificate_issuer_id = ci.id',
				array('issuer_name', 'issuer_email', 'issuer_phone_number', 'issuer_logo_file_id')
			);
			
			$query->joinInner(array('lscl' => 'link_student_classes'), 'lscl.studentid = s.id',
				array("grades" => "(CASE WHEN SUM(camark * classes.custom_2 / 100.0) >= 50.0 then '" . t('Pass') . "' else '" . t('Fail') . "' end)"))
				->joinLeft(array('classes'), 'classes.id = lscl.classid', array('credits' => 'SUM(maxcredits)'))
				->joinLeft(array('cm' => 'class_modules'), 'classes.class_modules_id = cm.id',
					array('nqf_level' => 'custom_1', 'title', 'external_id'))
				->joinLeft(array('lc' => 'lookup_coursetype'), 'lc.id = cm.lookup_coursetype_id', array('coursetype'))
			;

			$db = Zend_Db_Table_Abstract::getDefaultAdapter();

			$query->columns(array(
					'p.national_id',
					'lsc.examdate',
					'lsc.certificate_issuer_id',
					'i.address1',
					'i.address2',
					'i.city',
					'i.postalcode',
					'i.phone',
					'i.fax',
					'saqa_id' => 'p.custom_field2',
					'nqf_max' => new Zend_Db_Expr($db->quote('3')),
					'student_id' => 's.id',
				)
			);

			$query->group(array('cm.id', 's.id'));
			$headers[] = "AQP";
			$headers[] = "AQP E-mail";
			$headers[] = "AQP Phone";
			$headers[] = "AQP Logo";

			$headers[] = "Grade";
			$headers[] = "Credits";
			$headers[] = "NQF Level";
			$headers[] = "Module Name";
			$headers[] = "Course Type";
			$headers[] = "Module Number";

			$headers[] = "National ID";
			$headers[] = "Exam Date";
			$headers[] = "Certificate Issuer";
			$headers[] = "Institution Address 1";
			$headers[] = "Institution Address 2";
			$headers[] = "Institution City";
			$headers[] = "Institution Postal Code";
			$headers[] = "Institution Phone Number";
			$headers[] = "Institution Fax Number";
			$headers[] = "SAQA ID";
			$headers[] = "NQF Max";
			$headers[] = "Student ID";

			$query->order(array('p.last_name', 'p.first_name', 'p.national_id', 'lc.coursetype', 'cm.external_id'));

			$rowArray = $db->fetchAll($query);
			$this->view->assign('query', $query->__toString());

			$this->viewAssignEscaped("headers", $headers);
			$this->viewAssignEscaped("output", $rowArray);

		}
        $this->view->assign('criteria', $criteria);
	}

	public function ssCompAction() {
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['ques'] = $this->getSanParam ( 'ques' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		if ($criteria ['go']) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
			$sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp, compres as cmpr';
			$where = array('p.is_deleted = 0');
			$whr = array();
			$where []= 'cmpr.person = p.id';
			$where []= 'cmp.person = p.id';
			$where []= ' p.primary_qualification_option_id = q.id and p.facility_id = f.id and f.location_id = l.id ';
			if ($criteria ['facilityInput']) {
				$where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
			}
			$where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option WHERE parent_id = ' . $criteria ['qualification_id'] . ') ';
			$where []= 'cmpr.active = \'Y\'';
			$where []= 'cmpr.res = 1';
			$where []= 'cmp.active = \'Y\'';
			if($criteria ['qualification_id']=="6")
			{
				$whr []= 'cmp.question IN ('."'".str_replace(",","','",$this->getSanParam ( 'listcq' ))."'".')';
			}
			if($criteria ['qualification_id']=="7")
			{
				$qs=explode(",",$this->getSanParam ( 'ques' ));
				$nms=explode("~",$this->getSanParam ( 'listdq' ));
				foreach ( $qs as $kys => $vls ) {
					$whr []= 'cmp.question IN ('."'".str_replace(",","','",$nms[$vls])."'".')';
				}
			}
			if($criteria ['qualification_id']=="8")
			{
				$qs=explode(",",$this->getSanParam ( 'ques' ));
				$nms=explode("~",$this->getSanParam ( 'listnq' ));
				foreach ( $qs as $kys => $vls ) {
					$whr []= 'cmp.question IN ('."'".str_replace(",","','",$nms[$vls])."'".')';
				}
			}
			if($criteria ['qualification_id']=="9")
			{
				$whr []= 'cmp.question IN ('."'".str_replace(",","','",$this->getSanParam ( 'listpq' ))."'".')';
			}
			if( !empty($where) ){ $sql .= ' WHERE ' . implode(' AND ', $where); }
			if( !empty($whr) ){ $sql .= ' AND (' . implode(' OR ', $whr) . ')'; }
			$rowArray = $db->fetchAll ( $sql );
			$qss=array();
			$nmss=array();
			if($criteria ['qualification_id']=="6")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listcq' ));
			}
			if($criteria ['qualification_id']=="7")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listdq' ));
			}
			if($criteria ['qualification_id']=="8")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listnq' ));
			}
			if($criteria ['qualification_id']=="9")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listpq' ));
			}

			$ct=0;
			$rss=array();
			foreach ( $qss as $kys => $vls ) {
				$rss[$ct]=0;
				$ctt=0;
				$wss=explode(",",$nmss[$vls]);
				foreach ( $wss as $kyss => $vlss ) {
					foreach ( $rowArray as $kss => $vss ) {
						if($vlss." " == $vss['question']." ")
						{
							if($vss['option']=="A")
							{
								$rss[$ct]=$rss[$ct]+4;
							}
							else
							{
								if($vss['option']=="B")
								{
									$rss[$ct]=$rss[$ct]+3;
								}
								else
								{
									if($vss['option']=="C")
									{
										$rss[$ct]=$rss[$ct]+2;
									}
									else
									{
										if($vss['option']=="D")
										{
											$rss[$ct]=$rss[$ct]+1;
										}
									}
								}
							}
							$ctt=$ctt+1;
						}
					}
				}
				if($ctt>0)
					$rss[$ct]=number_format((($rss[$ct]/(4*$ctt))*100),2);
				$ct=$ct+1;
			}
			$this->viewAssignEscaped ( 'results', $rowArray );
			$this->viewAssignEscaped ( 'rss', $rss );
		}
		$this->view->assign ( 'criteria', $criteria );
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
				$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
	}

	public function ssCompcompAction() {
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['Questions'] = $this->getSanParam ( 'Questions' );
		$criteria ['outputType'] = $this->getSanParam ( 'outputType' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		if ($criteria ['go']) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$prsns=array();
			$prsnscnt=0;
			if($criteria ['qualification_id']=="6")
			{
				$sql='SELECT `person`, SUM(-(ASCII(`option`)-69)) `sm` FROM `comp`';
				$whr = array();
				$whr []= '`question` IN ('."'".str_replace(",","','",$this->getSanParam ( 'listcq' ))."'".')';
				$sql .= ' WHERE `active` = \'Y\' AND `option` <> \'E\' AND `option` <> \'F\' AND (' . implode(' OR ', $whr) . ')';
				$sql .= ' GROUP BY `person`';
				$rowArray = $db->fetchAll ( $sql );
				$tlques=explode(",",$this->getSanParam ( 'listcq' ));
				$ttlques=count($tlques);
				$qs=explode('$',$this->getSanParam ( 'Questions' ));
				foreach ( $qs as $kys => $vls ) {
					$fr=explode('^',$vls);
					$min=0;
					$max=0;
					if($fr[2]=="100")
					{
						$min=90;
						$max=100;
					}
					else
					{
						if($fr[2]=="89")
						{
							$min=75;
							$max=90;
						}
						else
						{
							if($fr[2]=="74")
							{
								$min=60;
								$max=75;
							}
							else
							{
								$min=1;
								$max=60;
							}
						}
					}
					foreach ( $rowArray as $prsn => $mrk ) {
						$prcnt=number_format((($mrk['sm']/(4*$ttlques))*100),2);
						if($prcnt>$min && $prcnt<=$max)
						{
							$prsns[$prsnscnt]=$mrk['person'];
							$prsnscnt=$prsnscnt+1;
						}
					}
				}
			}
			if($criteria ['qualification_id']=="7")
			{
				$qs=explode('$',$this->getSanParam ( 'Questions' ));
				$nms=explode("~",$this->getSanParam ( 'listdq' ));
				foreach ( $qs as $kys => $vls ) {
					$sql='SELECT `person`, SUM(-(ASCII(`option`)-69)) `sm` FROM `comp`';
					$whr = array();
					$fr=explode('^',$vls);
					$whr []= '`question` IN ('."'".str_replace(",","','",$nms[$fr[1]])."'".')';
					$sql .= ' WHERE `active` = \'Y\' AND `option` <> \'E\' AND `option` <> \'F\' AND (' . implode(' OR ', $whr) . ')';
					$sql .= ' GROUP BY `person`';
					$rowArray = $db->fetchAll ( $sql );
					$tlques=explode(",",$nms[$fr[1]]);
					$ttlques=count($tlques);
					$min=0;
					$max=0;
					if($fr[2]=="100")
					{
						$min=90;
						$max=100;
					}
					else
					{
						if($fr[2]=="89")
						{
							$min=75;
							$max=90;
						}
						else
						{
							if($fr[2]=="74")
							{
								$min=60;
								$max=75;
							}
							else
							{
								$min=1;
								$max=60;
							}
						}
					}
					foreach ( $rowArray as $prsn => $mrk ) {
						$prcnt=number_format((($mrk['sm']/(4*$ttlques))*100),2);
						if($prcnt>$min && $prcnt<=$max)
						{
							$prsns[$prsnscnt]=$mrk['person'];
							$prsnscnt=$prsnscnt+1;
						}
					}
				}
			}
			if($criteria ['qualification_id']=="8")
			{
				$qs=explode('$',$this->getSanParam ( 'Questions' ));
				$nms=explode("~",$this->getSanParam ( 'listnq' ));
				foreach ( $qs as $kys => $vls ) {
					$sql='SELECT `person`, SUM(-(ASCII(`option`)-69)) `sm` FROM `comp`';
					$whr = array();
					$fr=explode('^',$vls);
					$whr []= '`question` IN ('."'".str_replace(",","','",$nms[$fr[1]])."'".')';
					$sql .= ' WHERE `active` = \'Y\' AND `option` <> \'E\' AND `option` <> \'F\' AND (' . implode(' OR ', $whr) . ')';
					$sql .= ' GROUP BY `person`';
					$rowArray = $db->fetchAll ( $sql );
					$tlques=explode(",",$nms[$fr[1]]);
					$ttlques=count($tlques);
					$min=0;
					$max=0;
					if($fr[2]=="100")
					{
						$min=90;
						$max=100;
					}
					else
					{
						if($fr[2]=="89")
						{
							$min=75;
							$max=90;
						}
						else
						{
							if($fr[2]=="74")
							{
								$min=60;
								$max=75;
							}
							else
							{
								$min=1;
								$max=60;
							}
						}
					}
					foreach ( $rowArray as $prsn => $mrk ) {
						$prcnt=number_format((($mrk['sm']/(4*$ttlques))*100),2);
						if($prcnt>$min && $prcnt<=$max)
						{
							$prsns[$prsnscnt]=$mrk['person'];
							$prsnscnt=$prsnscnt+1;
						}
					}
				}
			}
			if($criteria ['qualification_id']=="9")
			{
				$sql='SELECT `person`, SUM(-(ASCII(`option`)-69)) `sm` FROM `comp`';
				$whr = array();
				$whr []= '`question` IN ('."'".str_replace(",","','",$this->getSanParam ( 'listpq' ))."'".')';
				$sql .= ' WHERE `active` = \'Y\' AND `option` <> \'E\' AND `option` <> \'F\' AND (' . implode(' OR ', $whr) . ')';
				$sql .= ' GROUP BY `person`';
				$rowArray = $db->fetchAll ( $sql );
				$tlques=explode(",",$this->getSanParam ( 'listpq' ));
				$ttlques=count($tlques);
				$qs=explode('$',$this->getSanParam ( 'Questions' ));
				foreach ( $qs as $kys => $vls ) {
					$fr=explode('^',$vls);
					$min=0;
					$max=0;
					if($fr[2]=="100")
					{
						$min=90;
						$max=100;
					}
					else
					{
						if($fr[2]=="89")
						{
							$min=75;
							$max=90;
						}
						else
						{
							if($fr[2]=="74")
							{
								$min=60;
								$max=75;
							}
							else
							{
								$min=1;
								$max=60;
							}
						}
					}
					foreach ( $rowArray as $prsn => $mrk ) {
						$prcnt=number_format((($mrk['sm']/(4*$ttlques))*100),2);
						if($prcnt>$min && $prcnt<=$max)
						{
							$prsns[$prsnscnt]=$mrk['person'];
							$prsnscnt=$prsnscnt+1;
						}
					}
				}
			}
			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
			$sql = 'SELECT  DISTINCT p.`id`, p.`first_name` ,  p.`last_name` ,  p.`gender` FROM `person` as p, facility as f, ('.$location_sub_query.') as l, `person_qualification_option` as q WHERE p.`primary_qualification_option_id` = q.`id` and p.facility_id = f.id and f.location_id = l.id AND p.`primary_qualification_option_id` IN (SELECT `id` FROM `person_qualification_option` WHERE `parent_id` = ' . $criteria ['qualification_id'] . ') AND p.`is_deleted` = 0 AND p.`id` IN (';
			if(count($prsns)>0)
			{
				foreach ( $prsns as $k => $v ) {
					$sql = $sql . $v . ',';
				}
			}
			$sql = $sql . '0';
			if ($criteria ['facilityInput']) {
				$sql = $sql . ') AND p.facility_id = "' . $criteria ['facilityInput'] . '";';
			}
            else {
                $sql = $sql . ');';
            }
			$rowArray = $db->fetchAll ( $sql );
			if ($criteria ['outputType']) {
				$this->sendData ( $this->reportHeaders ( false, $rowArray ) );
			}
			$this->viewAssignEscaped ( 'results', $rowArray );
		}
		$this->view->assign ( 'criteria', $criteria );
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
				$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
	}

	public function ssCompcsvAction() {
		$v1=explode("~",$this->getSanParam ( 'v1' ));
		$v2=explode("~",$this->getSanParam ( 'v2' ));
        $p=$this->getSanParam ( 'p' );
        $d=$this->getSanParam ( 'd' );
        $s=$this->getSanParam ( 's' );
        $f=$this->getSanParam ( 'f' );
		$this->viewAssignEscaped ( 'v1', $v1 );
		$this->viewAssignEscaped ( 'v2', $v2 );
		$this->viewAssignEscaped ( 'p',  $p);
		$this->viewAssignEscaped ( 'd',  $d);
		$this->viewAssignEscaped ( 's',  $s);
		$this->viewAssignEscaped ( 'f',  $f);
	}

	public function ssDetailAction() {
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['training_title_option_id'] = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['ques'] = $this->getSanParam ( 'ques' );
		$criteria ['score_id'] = $this->getSanParam ( 'score_id' );
		$criteria ['primarypatients'] = $this->getSanParam ( 'primarypatients' );
		$criteria ['hivInput'] = $this->getSanParam ( 'hivInput' );
		$criteria ['trainer_type_option_id1'] = $this->getSanParam ( 'trainer_type_option_id1' );
		$criteria ['grp1'] = $this->getSanParam ( 'grp1' );
		$criteria ['grp2'] = $this->getSanParam ( 'grp2' );
		$criteria ['grp3'] = $this->getSanParam ( 'grp3' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
		if ($criteria ['go']) {
			$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
			$num_locs = $this->setting('num_location_tiers');
			list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
			$sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp, compres as cmpr';
			if ( $criteria['training_title_option_id'] ) {
				 $sql .= ', person_to_training as ptt ';
				 $sql .= ', training as tr  ';
			}
			$where = array('p.is_deleted = 0');
			$whr = array();
			$where []= 'cmpr.person = p.id';
			$where []= 'cmp.person = p.id';
			$where []= ' p.primary_qualification_option_id = q.id and p.facility_id = f.id and f.location_id = l.id ';
			if ($criteria ['facilityInput']) {
				$where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
			}
			if ( $criteria['training_title_option_id'] ) {
				$where []= ' p.id = ptt.person_id AND ptt.training_id = tr.id AND tr.training_title_option_id = ' . ($criteria ['training_title_option_id']) . ' ';
			}
			$where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option WHERE parent_id = ' . $criteria ['qualification_id'] . ') ';
			$where []= 'cmpr.active = \'Y\'';
			$where []= 'cmpr.res = 1';
			$where []= 'cmp.active = \'Y\'';
			if($criteria ['qualification_id']=="6")
			{
				$whr []= 'cmp.question IN ('."'".str_replace(",","','",$this->getSanParam ( 'listcq' ))."'".')';
			}
			if($criteria ['qualification_id']=="7")
			{
				$qs=explode(",",$this->getSanParam ( 'ques' ));
				$nms=explode("~",$this->getSanParam ( 'listdq' ));
				foreach ( $qs as $kys => $vls ) {
					$whr []= 'cmp.question IN ('."'".str_replace(",","','",$nms[$vls])."'".')';
				}
			}
			if($criteria ['qualification_id']=="8")
			{
				$qs=explode(",",$this->getSanParam ( 'ques' ));
				$nms=explode("~",$this->getSanParam ( 'listnq' ));
				foreach ( $qs as $kys => $vls ) {
					$whr []= 'cmp.question IN ('."'".str_replace(",","','",$nms[$vls])."'".')';
				}
			}
			if($criteria ['qualification_id']=="9")
			{
				$whr []= 'cmp.question IN ('."'".str_replace(",","','",$this->getSanParam ( 'listpq' ))."'".')';
			}
			if( !empty($where) ){ $sql .= ' WHERE ' . implode(' AND ', $where); }
			if( !empty($whr) ){ $sql .= ' AND (' . implode(' OR ', $whr) . ')'; }

			$rowArray = $db->fetchAll ( $sql );
			$qss=array();
			$nmss=array();
			if($criteria ['qualification_id']=="6")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listcq' ));
			}
			if($criteria ['qualification_id']=="7")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listdq' ));
			}
			if($criteria ['qualification_id']=="8")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listnq' ));
			}
			if($criteria ['qualification_id']=="9")
			{
				$qss=explode(",",$this->getSanParam ( 'ques' ));
				$nmss=explode("~",$this->getSanParam ( 'listpq' ));
			}

			$ct=0;
			$rss=array();
			foreach ( $qss as $kys => $vls ) {
				$rss[$ct]=0;
				$ctt=0;
				$wss=explode(",",$nmss[$vls]);
				foreach ( $wss as $kyss => $vlss ) {
					foreach ( $rowArray as $kss => $vss ) {
						if($vlss." " == $vss['question']." ")
						{
							if($vss['option']=="A")
							{
								$rss[$ct]=$rss[$ct]+4;
							}
							else
							{
								if($vss['option']=="B")
								{
									$rss[$ct]=$rss[$ct]+3;
								}
								else
								{
									if($vss['option']=="C")
									{
										$rss[$ct]=$rss[$ct]+2;
									}
									else
									{
										if($vss['option']=="D")
										{
											$rss[$ct]=$rss[$ct]+1;
										}
									}
								}
							}
							$ctt=$ctt+1;
						}
					}
				}
				if($ctt>0)
					$rss[$ct]=number_format((($rss[$ct]/(4*$ctt))*100),2);
				$ct=$ct+1;
			}
			$this->viewAssignEscaped ( 'results', $rowArray );
			$this->viewAssignEscaped ( 'rss', $rss );
		}
		$this->view->assign ( 'criteria', $criteria );
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		require_once ('models/table/TrainingTitleOption.php');
		$titleArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $titleArray );
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
				$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );
	}

	public function ssProfAction() {
		if (! $this->hasACL ( 'view_people' ) and ! $this->hasACL ( 'edit_people' )) {
			$this->doNoAccessError ();
		}
		$criteria = array ();
		list($criteria, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
		$criteria ['facilityInput'] = $this->getSanParam ( 'facilityInput' );
		$criteria ['training_title_option_id'] = $this->getSanParam ( 'training_title_option_id' );
		$criteria ['qualification_id'] = $this->getSanParam ( 'qualification_id' );
		$criteria ['ques'] = $this->getSanParam ( 'ques' );
		$criteria ['go'] = $this->getSanParam ( 'go' );
        $criteria ['all'] = $this->getSanParam ( 'all' );
		if ($criteria ['go']) {
#			var_dump ($_GET);
#			exit;
            if ($criteria ['all']) {
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $num_locs = $this->setting('num_location_tiers');
                list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);
                $sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp, compres as cmpr';
                if ( $criteria['training_title_option_id'] ) {
                     $sql .= ', person_to_training as ptt ';
                     $sql .= ', training as tr  ';
                }
                $where = array('p.is_deleted = 0');
                $where []= 'cmpr.person = p.id';
                $where []= 'cmp.person = p.id';
                $where []= ' p.primary_qualification_option_id = q.id and p.facility_id = f.id and f.location_id = l.id ';
                if ($criteria ['facilityInput']) {
                    $where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
                }
                if ( $criteria['training_title_option_id'] ) {
                    $where []= ' p.id = ptt.person_id AND ptt.training_id = tr.id AND tr.training_title_option_id = ' . ($criteria ['training_title_option_id']) . ' ';
                }
                $where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option WHERE parent_id IN (6, 7, 8, 9) ) ';
                $where []= 'cmpr.active = \'Y\'';
                $where []= 'cmpr.res = 1';
                $where []= 'cmp.active = \'Y\'';
                $sql .= ' WHERE ' . implode(' AND ', $where);
die (__LINE__ . " - " . $sql);

                $rowArray = $db->fetchAll ( $sql );
                $qss=array();
                $nmss=array();
                $qss=explode(",","0,1,2,3,4,5,6,7");
                $nmss=explode("~","1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,200~01,02,03,04,05,06,07,08,09~31,32,33,34,35,36,37,38~41,42,43,44,45~51,52,53,54,55,56,57,58,59,510,511,512,513,514,515,516,517,518~61,62,63,64,65,66,67~71,72,73,74,75,76,77,78,79,710,711~21,22,23");
                $ct;
                $ct=0;
                $rssA=array();
                $rssB=array();
                $rssC=array();
                $rssD=array();
                $rssE=array();
                $ctt;
                foreach ( $qss as $kys => $vls ) {
                    $rssA[$ct]=0;
                    $rssB[$ct]=0;
                    $rssC[$ct]=0;
                    $rssD[$ct]=0;
                    $rssE[$ct]=0;
                    $ctt=0;
                    $wss=explode(",",$nmss[$vls]);
                    foreach ( $wss as $kyss => $vlss ) {
                        foreach ( $rowArray as $kss => $vss ) {
                            if($vlss." " == $vss['question']." ")
                            {
                                if($vss['option']=="A")
                                {
                                    $rssA[$ct]=$rssA[$ct]+1;
                                }
                                else
                                {
                                    if($vss['option']=="B")
                                    {
                                        $rssB[$ct]=$rssB[$ct]+1;
                                    }
                                    else
                                    {
                                        if($vss['option']=="C")
                                        {
                                            $rssC[$ct]=$rssC[$ct]+1;
                                        }
                                        else
                                        {
                                            if($vss['option']=="D")
                                            {
                                                $rssD[$ct]=$rssD[$ct]+1;
                                            }
                                            else
                                            {
                                                if($vss['option']=="E")
                                                {
                                                    $rssE[$ct]=$rssE[$ct]+1;
                                                }
                                            }
                                        }
                                    }
                                }
                                $ctt=$ctt+1;
                            }
                        }
                    }
                    if($ctt>0) {
                        $rssA[$ct]=number_format((($rssA[$ct]/$ctt)*100),2);
                        $rssB[$ct]=number_format((($rssB[$ct]/$ctt)*100),2);
                        $rssC[$ct]=number_format((($rssC[$ct]/$ctt)*100),2);
                        $rssD[$ct]=number_format((($rssD[$ct]/$ctt)*100),2);
                        $rssE[$ct]=number_format((($rssE[$ct]/$ctt)*100),2);
                    }
                    $ct=$ct+1;
                }
                $this->viewAssignEscaped ( 'results', $rowArray );
                $this->viewAssignEscaped ( 'rssA', $rssA );
                $this->viewAssignEscaped ( 'rssB', $rssB );
                $this->viewAssignEscaped ( 'rssC', $rssC );
                $this->viewAssignEscaped ( 'rssD', $rssD );
                $this->viewAssignEscaped ( 'rssE', $rssE );
            } else {
                $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
                $num_locs = $this->setting('num_location_tiers');
                list($field_name,$location_sub_query) = Location::subquery($num_locs, $location_tier, $location_id);

                $sql = 'select DISTINCT cmp.person, cmp.question, cmp.option from person as p, person_qualification_option as q, facility as f, ('.$location_sub_query.') as l, comp as cmp, compres as cmpr';
                if ( $criteria['training_title_option_id'] ) {
                     $sql .= ', person_to_training as ptt ';
                     $sql .= ', training as tr  ';
                }
                $where = array('p.is_deleted = 0');
                $whr = array();
                $where []= 'cmpr.person = p.id';
                $where []= 'cmp.person = p.id';
                $where []= ' p.primary_qualification_option_id = q.id and p.facility_id = f.id and f.location_id = l.id ';
                if ($criteria ['facilityInput']) {
                    $where []= ' p.facility_id = "' . $criteria ['facilityInput'] . '"';
                }
                if ( $criteria['training_title_option_id'] ) {
                    $where []= ' p.id = ptt.person_id AND ptt.training_id = tr.id AND tr.training_title_option_id = ' . ($criteria ['training_title_option_id']) . ' ';
                }
                $where []= ' primary_qualification_option_id IN (SELECT id FROM person_qualification_option WHERE parent_id = ' . $criteria ['qualification_id'] . ') ';
                $where []= 'cmpr.active = \'Y\'';
                $where []= 'cmpr.res = 1';
                $where []= 'cmp.active = \'Y\'';

				$qry = "SELECT id FROM competencies_questions WHERE competencyid IN (" . implode(",", $_GET['competencyselect']) . ")";
                $questionresult = $db->fetchAll ($qry);
                $_q = array();
                foreach ($questionresult as $qres){
                	$_q[] = $qres['id'];
                }
                $whr[] = 'cmp.question IN (' . implode(",", $_q) . ')';

            	if( !empty($where) ){ $sql .= ' WHERE ' . implode(' AND ', $where); }
				if( !empty($whr) ){ $sql .= ' AND (' . implode(' OR ', $whr) . ')'; }
				//todo check everything same here!
                $rowArray = $db->fetchAll ( $sql );
                $qss=array();
                $nmss=array();
                if($criteria ['qualification_id']=="6")
                {
                    $qss=explode(",",$this->getSanParam ( 'ques' ));
                    $nmss=explode("~",$this->getSanParam ( 'listcq' ));
                }
                if($criteria ['qualification_id']=="7")
                {
                    $qss=explode(",",$this->getSanParam ( 'ques' ));
                    $nmss=explode("~",$this->getSanParam ( 'listdq' ));
                }
                if($criteria ['qualification_id']=="8")
                {
                    $qss=explode(",",$this->getSanParam ( 'ques' ));
                    $nmss=explode("~",$this->getSanParam ( 'listnq' ));
                }
                if($criteria ['qualification_id']=="9")
                {
                    $qss=explode(",",$this->getSanParam ( 'ques' ));
                    $nmss=explode("~",$this->getSanParam ( 'listpq' ));
                }
                $ct=0;
                $rssA=array();
                $rssB=array();
                $rssC=array();
                $rssD=array();
                $rssE=array();

                foreach ( $qss as $kys => $vls ) {
                    $rssA[$ct]=0;
                    $rssB[$ct]=0;
                    $rssC[$ct]=0;
                    $rssD[$ct]=0;
                    $rssE[$ct]=0;
                    $ctt=0;
                    $wss=explode(",",$nmss[$vls]);
                    foreach ( $wss as $kyss => $vlss ) {
                        foreach ( $rowArray as $kss => $vss ) {
                            if($vlss." " == $vss['question']." ")
                            {
                                if($vss['option']=="A")
                                {
                                    $rssA[$ct]=$rssA[$ct]+1;
                                }
                                else
                                {
                                    if($vss['option']=="B")
                                    {
                                        $rssB[$ct]=$rssB[$ct]+1;
                                    }
                                    else
                                    {
                                        if($vss['option']=="C")
                                        {
                                            $rssC[$ct]=$rssC[$ct]+1;
                                        }
                                        else
                                        {
                                            if($vss['option']=="D")
                                            {
                                                $rssD[$ct]=$rssD[$ct]+1;
                                            }
                                            else
                                            {
                                                if($vss['option']=="E")
                                                {
                                                    $rssE[$ct]=$rssE[$ct]+1;
                                                }
                                            }
                                        }
                                    }
                                }
                                $ctt=$ctt+1;
                            }
                        }
                    }
                    if($ctt>0) {
                        $rssA[$ct]=number_format((($rssA[$ct]/$ctt)*100),2);
                        $rssB[$ct]=number_format((($rssB[$ct]/$ctt)*100),2);
                        $rssC[$ct]=number_format((($rssC[$ct]/$ctt)*100),2);
                        $rssD[$ct]=number_format((($rssD[$ct]/$ctt)*100),2);
                        $rssE[$ct]=number_format((($rssE[$ct]/$ctt)*100),2);
                    }
                    $ct=$ct+1;
                }
                $this->viewAssignEscaped ( 'results', $rowArray );
                $this->viewAssignEscaped ( 'rssA', $rssA );
                $this->viewAssignEscaped ( 'rssB', $rssB );
                $this->viewAssignEscaped ( 'rssC', $rssC );
                $this->viewAssignEscaped ( 'rssD', $rssD );
                $this->viewAssignEscaped ( 'rssE', $rssE );
            }
		}
		$this->view->assign ( 'criteria', $criteria );
		$this->viewAssignEscaped ( 'locations', Location::getAll() );
		require_once ('models/table/TrainingTitleOption.php');
		$titleArray = TrainingTitleOption::suggestionList ( false, 10000 );
		$this->viewAssignEscaped ( 'courses', $titleArray );
		$qualificationsArray = OptionList::suggestionListHierarchical ( 'person_qualification_option', 'qualification_phrase', false, false );
		$this->viewAssignEscaped ( 'qualifications', $qualificationsArray );
		$rowArray = OptionList::suggestionList ( 'facility', array ('facility_name', 'id' ), false, 9999 );
		$facilitiesArray = array ();
		foreach ( $rowArray as $key => $val ) {
			if ($val ['id'] != 0)
				$facilitiesArray [] = $val;
		}
		$this->viewAssignEscaped ( 'facilities', $facilitiesArray );


		$helper = new Helper();
		$this->viewAssignEscaped("competencies",$helper->getCompetencies());
		$this->viewAssignEscaped("compqualification", $helper->getQualificationCompetencies());
	}

	public function ssProfcsvAction() {
		$v1=explode("~",$this->getSanParam ( 'v1' ));
		$v2=explode("~",$this->getSanParam ( 'v2' ));
		$v3=explode("~",$this->getSanParam ( 'v3' ));
		$v4=explode("~",$this->getSanParam ( 'v4' ));
		$v5=explode("~",$this->getSanParam ( 'v5' ));
		$v6=explode("~",$this->getSanParam ( 'v6' ));
        $p=$this->getSanParam ( 'p' );
        $d=$this->getSanParam ( 'd' );
        $s=$this->getSanParam ( 's' );
        $f=$this->getSanParam ( 'f' );
		$this->viewAssignEscaped ( 'v1', $v1 );
		$this->viewAssignEscaped ( 'v2', $v2 );
		$this->viewAssignEscaped ( 'v3', $v3 );
		$this->viewAssignEscaped ( 'v4', $v4 );
		$this->viewAssignEscaped ( 'v5', $v5 );
		$this->viewAssignEscaped ( 'v6', $v6 );
		$this->viewAssignEscaped ( 'p',  $p);
		$this->viewAssignEscaped ( 'd',  $d);
		$this->viewAssignEscaped ( 's',  $s);
		$this->viewAssignEscaped ( 'f',  $f);
	}

    public function employeesAction(){
        $locations = Location::getAll();
        $criteria = $this->getAllParams();
        

        $db = $this->dbfunc();

        if (isset($criteria['go']) && $criteria['go']) {
            //print_r($criteria);//TA:1000
            $select = self::employeeFilterQuery($criteria);
            if (!is_a($select, "Zend_Db_Select", false)) {
                $status = ValidationContainer::instance();
                $status->setStatusMessage(t('Error'));
            } else {

               $select->distinct();
         
                $tables = $select->getPart(Zend_Db_Select::FROM);
                $cols = $select->getPart(Zend_Db_Select::COLUMNS);
                //TA:#464
//                 if (!array_key_exists('link_mechanism_employee', $tables)) {
//                     //TA:#419 using LEFT JOIN is cause of query execution delay we use JOIN but it will display only employee records with mechanisms
//                     $select->join('link_mechanism_employee', 'link_mechanism_employee.employee_id = employee.id', array());
//                 }
//                 if (!array_key_exists('mechanism_option', $tables)) {
//                     $select->joinLeft('mechanism_option', 'mechanism_option.id = link_mechanism_employee.mechanism_option_id', array()); 
//                 }
                if (!array_key_exists('link_employee_facility', $tables)) {
                    //TA:#419 using LEFT JOIN is cause of query execution delay we use JOIN but it will display only employee records with mechanisms
                    $select->join('link_employee_facility', 'link_employee_facility.employee_id = employee.id', array());
                }
                if (!array_key_exists('mechanism_option', $tables)) {
                    $select->joinLeft('mechanism_option', 'mechanism_option.id = link_employee_facility.mechanism_option_id', array());
                }
                /////
                
                //TA:#415 make visible results by user mechanism accessebility
                if (!$this->hasACL('mechanism_option_all')) {
                    $select->joinLeft(array('user_to_mechanism_access'),
                        'user_to_mechanism_access.mechanism_option_id = mechanism_option.id', array());
                    $select->where('user_to_mechanism_access.user_id = ?', $this->isLoggedIn());
                }else{
                    if (!$this->hasACL('training_organizer_option_all')) {
                        $select->joinLeft(array('user_to_organizer_access'),
                            'user_to_organizer_access.training_organizer_option_id = mechanism_option.owner_id', array());
                        $select->where('user_to_organizer_access.user_id = ?', $this->isLoggedIn());
                    }
                }
                
                $c = array_map(
                    function ($item) {
                        //TA:#419 a lot of changes with headers
                        $header_names = array(
                             'active' => t('Active HRH'), 
                            'role_phrase' => t('Disaggregate Cadre'),
                            'qualification_phrase' => t('Occupational Classification'),
                            'employee_code' => t('Employee Code'),
                            'partner' => t('Partner Name'),
                            'employee_dsdmodel_phrase' => t('Service Delivery Model'),
                            'employee_dsdteam_phrase' => t('Service Delivery Team'),
                            'province_name' => t('Region A (Province)'),
                            'district_name' => t('Region B (Health District)'),
                            'region_c_name' => t('Region C (Local Region)'),
                            'facility_name' => t('Facility') . ' ' . t('Name'),
                            'facility_type_phrase' => t('Facility Type'),
                            'hiv_fte_related' => t('Hours Worked per Week (per Site)'), //TA:#439
                            'contract_start_date' => t('Contract Start Date'), 
                            'contract_end_date' => t('Contract End Date'), 
                            'intended_transition' => t('Intended Transition'),
                            'transition_other' => t('Intended Transition Other'),
                            'transition_date' => t('Intended Transition Date'),
                            'actual_transition' => t('Actual Transition Outcome'),
                            'transition_complete_other' => t('Actual Transition Outcome, Other'),
                            'transition_complete_date' => t('Actual Transition Date'),
                            'salary_or_stipend' => t('Salaried or Stipend'), //TA:#465
                            'funded_hours_per_week' => t('Hours Worked per Week (FTE)'),
                            'salary' => t('Annual Salary (R)'),
                            'benefits' => t('Annual Benefits (R)'),
                            'financial_benefits_description_option' => t('Financial Benefits Description'),//TA:#466
                            'non_financial_benefits' => t('Non-financial Benefits (R)'), //TA:#467
                            'non_financial_benefits_description_option' => t('Non-financial Benefits Description'),//TA:#468
                            'professional_development' => t('Professional Development (R)'), //TA:#469
                            'professional_development_description_option' => t('Professional Development (R) Description'),//TA:#474
                            'additional_expenses' => t('Additional Expenses (R)'),
                            'stipend' => t('Annual Stipend (R)'),
                            'annual_cost' => t('Annual Cost (R)'),
                            'impl_mech_partner_name' => t('Implementing Mechanism Prime Partner Name'),
                            'funder_phrase' => t('Implementing Agency'),
                            'external_id' => t('Implementing Mechanism Identifier'),
                            'mechanism_phrase' => t('Implementing Mechanism Name'),
                            'percentage' => t('FTE percentage'),
                            'mechanism_end_date' => t('Implementing Mechanism Funding End Date'),
                        );
                        if ($item[2] !== null) {
                            return $header_names[$item[2]];
                        }
                        return $header_names[$item[1]];
                    },
                    $select->getPart(Zend_Db_Select::COLUMNS)
                );

                if (count($c)) {
                    $this->view->assign('headers', $c);
                    //TA:#419
                    // query was huge and took about 10 minutes to be run on DB (employee table LEFT JOIN with link_mechanism_employee 26,000*25,000=650,000,000 records in MYSQL cash)
                    // and finally crushed on client browser - javascript error
                    //then I use INNER JOIN instead of LEFT JOIN, so do not show employee records with no mechanism (may be it is OK)
                    //IF we will decide to show all employee records then we have to use limit and show results by portions
                    // LIMIT [start with row],[offset]
                   // $select = $select . " LIMIT 0,1000"; // show rows since 1 to 1000 including
                    //$select = $select . " LIMIT 5,10"; // show rows since 6 to 15 including
                  // print $select . "<br><br>";
                   $this->view->assign('output',$db->fetchAll($select));
                }
            }
        }

        $choose = array("0" => '--' . t("All") . '--');

        $select = $db->select()
            ->from('partner', array('id', 'partner'))
            ->order('partner ASC');

        if (!$this->hasACL('training_organizer_option_all')) {
            $uid = $this->isLoggedIn();
            $select->join(array('user_to_organizer_access'),
                'user_to_organizer_access.training_organizer_option_id = partner.organizer_option_id', array())
                ->where('user_to_organizer_access.user_id = ?', $uid);
        }

        $partners = $choose + $db->fetchPairs($select);

        $facilities = $choose + $db->fetchPairs($db->select()
                ->from('facility', array('id', 'facility_name'))
                ->order('facility_name ASC')
            );

        $facilityTypes = $choose + $db->fetchPairs($db->select()
                ->from('facility_type_option', array('id', 'facility_type_phrase'))
                ->order('facility_type_phrase ASC')
            );

        $classifications = $choose + $db->fetchPairs($db->select()
                ->from('employee_qualification_option', array('id', 'qualification_phrase'))
                ->order('qualification_phrase ASC')
            );
        
        //TA:#419
        $employee_codes = $choose + $db->fetchPairs($db->select()
            ->from('employee', array('employee_code', 'employee_code'))
            ->order('employee_code ASC')
        );
        
        //TA:#419
        $dsd_models = $choose + $db->fetchPairs($db->select()
            ->from('employee_dsdmodel_option', array('id', 'employee_dsdmodel_phrase'))
            ->order('employee_dsdmodel_phrase ASC')
        );
        
        //TA:#419
        $dsd_teams = $choose + $db->fetchPairs($db->select()
            ->from('employee_dsdteam_option', array('id', 'employee_dsdteam_phrase'))
            ->order('employee_dsdteam_phrase ASC')
        );

        $roles = $choose + $db->fetchPairs($db->select()
                ->from('employee_role_option', array('id', 'role_phrase'))
                ->order('role_phrase ASC')
            );

        $transitions = $choose + $db->fetchPairs($db->select()
                ->from('employee_transition_option', array('id', 'transition_phrase'))
                ->order('transition_phrase ASC')
            );
        
        //TA:#419
        $transitions_other = $choose + $db->fetchPairs($db->select()
            ->from('employee', array('transition_other', 'transition_other'))
            ->group('transition_other')
            ->where('transition_other is not null')
            ->order('transition_other ASC')
        );
        $transitions_complete = $choose + $db->fetchPairs($db->select()
            ->from('employee_transition_complete_option', array('id', 'transition_complete_phrase'))
            ->order('transition_complete_phrase ASC'));
        $transitions_complete_other = $choose + $db->fetchPairs($db->select()
            ->from('employee', array('transition_complete_other', 'transition_complete_other'))
            ->group('transition_complete_other')
            ->where('transition_complete_other is not null')
            ->order('transition_complete_other ASC')
        );
        $agencies = $choose + $db->fetchPairs($db->select()
            ->from('partner_funder_option', array('id', 'funder_phrase'))
            ->order('funder_phrase ASC'));
        
        $mechanism_ids = $db->fetchPairs($db->select()
            ->from('mechanism_option', array('external_id','external_id'))
                    ->group('external_id')
                    ->where('external_id is not null')
                    ->order('external_id ASC')
                    );
       $mechanism_names = $db->fetchAll($db->select()
                    ->from('mechanism_option', array('id', 'mechanism_phrase'))
                    ->order('mechanism_phrase ASC'));

        $bases = $choose + $db->fetchPairs($db->select()
                ->from('employee_base_option', array('id', 'base_phrase'))
                ->order('base_phrase ASC')
            );

        $this->view->assign('partners', $partners);
        $this->view->assign('mech_partners', $partners);//TA:#419
       
        //TA:#466
        $select = $db->select()->from('employee_financial_benefits_description_option', array('id', 'financial_benefits_description_option'))->order('financial_benefits_description_option ASC');
        $this->view->assign('employee_financial_benefits_description', $choose + $db->fetchPairs($select));
        ///
        //TA:#468
        $select = $db->select()->from('employee_non_financial_benefits_description_option', array('id', 'non_financial_benefits_description_option'))->order('non_financial_benefits_description_option ASC');
        $this->view->assign('employee_non_financial_benefits_description', $choose + $db->fetchPairs($select));
        ///
        //TA:#474
        $select = $db->select()->from('employee_professional_development_description_option', array('id', 'professional_development_description_option'))->order('professional_development_description_option ASC');
        $this->view->assign('employee_professional_development_description', $choose + $db->fetchPairs($select));
        ///
        $this->view->assign('facilities', $facilities);
        $this->view->assign('facilityTypes', $facilityTypes);
        $this->view->assign('classifications', $classifications);
        $this->view->assign('employee_codes', $employee_codes); //TA:#419
        $this->view->assign('dsd_models', $dsd_models); //TA:#419
        $this->view->assign('dsd_teams', $dsd_teams); //TA:#419
        $this->view->assign('roles', $roles);
        $this->view->assign('transitions', $transitions);
        $this->view->assign('transitions_other', $transitions_other);//TA:#419
        $this->view->assign('transitions_complete', $transitions_complete); //TA:#419
        $this->view->assign('transitions_complete_other', $transitions_complete_other);//TA:#419
        $this->view->assign('agencies', $agencies);//TA:#419
        $this->view->assign('mechanism_ids', $mechanism_ids);//TA:#419
        $this->view->assign('mechanism_names', $mechanism_names);//TA:#419
        $this->view->assign('locations', $locations);
        $this->view->assign('bases', $bases);
        
        //TA:#293 set location multiple selection
        require_once ('views/helpers/Location.php');
        $criteria['district_id'] = regionFiltersGetDistrictIDMultiple($criteria);
        $criteria['region_c_id'] = regionFiltersGetLastIDMultiple('', $criteria);
         
        //TA:#293.1
//         $helper = new Helper();
//         $this->viewAssignEscaped('sites', $helper->getFacilities());
        
        $this->view->assign('criteria', $criteria);
    }
    
    //TA:#513
    public function getCurrentQuarter() {
        $n = date('n');
        if($n < 4){
            return "1";
        } elseif($n > 3 && $n <7){
            return "2";
        } elseif($n >6 && $n < 10){
            return "3";
        } elseif($n >9){
            return "4";
        }
        return "";
    }
    
    //TA:#511
    public function getCurrentQuarterStartDate(){
        $month = date('n');
        $year = date('Y');
        if ($month < 4) {
            return "01/01/" . $year;
        } elseif ($month > 3 && $n < 7) {
            return "04/01/" . $year;
        } elseif ($month > 6 && $n < 10) {
            return "07/01/" . $year;
        } elseif ($month > 9) {
            return "10/01/" . $year;
        } 
    }
    
    //TA:#511
    public function getPreviousQuarterStartDate(){
        $month = date('n');
        $year = date('Y');
        if ($month < 4) {
            return "10/01/" . ($year-1);
        } elseif ($month > 3 && $month < 7) {
            return "01/01/" . $year;
        } elseif ($month > 6 && $month < 10) {
            return "04/01/" . $year;
        } elseif ($month > 9) {
            return "07/01/" . $year;
        }
    }
    
    //TA:#511
    public function getPreviousQuarterEndDate(){
        $month = date('n');
        $year = date('Y');
        if ($month < 4) {
            return "12/31/" . ($year-1);
        } elseif ($month > 3 && $month < 7) {
            return "31/03/" . $year;
        } elseif ($month > 6 && $month < 10) {
            return "06/30/" . $year;
        } elseif ($month > 9) {
            return "09/30/" . $year;
        }
    }
  
    
    //TA:#499
    public function employees2Action(){
        $locations = Location::getAll();
        $criteria = $this->getAllParams();
       
        $db = $this->dbfunc();
        
        if (isset($criteria['go']) && $criteria['go']) {
            //print_r($criteria);
            $select = "";
            $more_join = "";
            $where =  "";
            $group =  " 
            partner.id,
            partner.partner,
            employee.is_active,
            employee.id,
			employee_role_option.role_phrase,
			employee_qualification_option.qualification_phrase,
			employee.employee_code,
			employee_dsdmodel_option.employee_dsdmodel_phrase,
			employee_dsdteam_option.employee_dsdteam_phrase,
			location_2.location_name,
			location_1.location_name,
			location.location_name,
			facility.facility_name,
			link_employee_facility.hiv_fte_related,
			mechanism_option.mechanism_phrase,
			mechanism_option.external_id,
			mechanism_option.end_date,
			employee.agreement_start_date,
			employee.agreement_end_date,
			employee_transition_option.transition_phrase,
			employee.transition_other,
			employee.transition_date,
			employee_transition_complete_option.transition_complete_phrase,
			employee.transition_complete_other,
			employee.transition_complete_date,
			employee.salary_or_stipend,
			employee.funded_hours_per_week,
			employee.salary,
			employee.benefits,
			employee.non_financial_benefits,
			employee.professional_development,
			employee.stipend,
			employee.annual_cost,
			partner_funder_option.funder_phrase,
			partner_1.partner ";
            $order =  "
            partner.partner,
			employee.id,
			employee.employee_code,
			employee_dsdmodel_option.employee_dsdmodel_phrase,
			location_2.location_name ";
            $header_names = array();
            
            //PARTNER ID
            if (isset($criteria['show_partnerid']) && $criteria['show_partnerid']) {
                $header_names['partnerid'] = t('Partner ID');
                $select .= "partner.id AS partnerid ";
            }
            
            // PARTNER NAME
            if (isset($criteria['show_partner']) && $criteria['show_partner']) {
                $header_names['partner'] = t('Partner Name');
                if($select !== ""){ $select .= ", "; }
                $select .= "partner.partner AS partner ";
            }
            if ((isset($criteria['partner']) && $criteria['partner'])) {
                if(is_array($criteria['partner'])){
                    if($criteria['partner'][0] > 0){
                        $where .= ' partner.id in ( ' . implode(",", $criteria['partner']) . ") ";
                    }
                }else{
                    $where .= ' partner.id = ' . $criteria['partner'];
                }
            }
            
            //ACTIVE HRH
            if (isset($criteria['show_is_active']) && $criteria['show_is_active']) {
                $header_names['active'] = t('Active HRH');
                if($select !== ""){ $select .= ", "; }
                $select .= "IF(employee.is_active = 1,'Active','Inactive') as is_active ";
            }
            if(isset($criteria['is_active'])) {
                if($where !== ""){ $where .= " AND "; }
                $where .= ' is_active = ' . $criteria['is_active'];
            }
            
            //POSITION ID
            if (isset($criteria['show_positionid']) && $criteria['show_positionid']) {
                $header_names['positionid'] = t('Position ID');
                if($select !== ""){ $select .= ", "; }
                $select .= "employee.id AS positionid ";
            }
            
            // CADRE
            if (isset($criteria['show_primary_role']) && $criteria['show_primary_role']) {
                $header_names['role_phrase'] = t('Disaggregate Cadre');
                if($select !== ""){ $select .= ", "; }
                $select .= "employee_role_option.role_phrase AS role_phrase ";
            }
            if (isset($criteria['primary_role']) && $criteria['primary_role']) {
                if(is_array($criteria['primary_role'])){
                    if($criteria['primary_role'][0] > 0){
                        if($where !== ""){ $where .= " AND "; }
                        $where .= 'employee_role_option.id in ( ' . implode(",", $criteria['primary_role']) . ")";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .= ' employee_role_option.id =' . $criteria['primary_role'];
                }
            }
            
            // CLASSIFICATION
            if (isset($criteria['show_classification']) && $criteria['show_classification']) {
                $header_names['qualification_phrase'] = t('Occupational Classification');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee_qualification_option.qualification_phrase AS qualification_phrase ";
            }
            if (isset($criteria['classification']) && $criteria['classification']) {
                if(is_array($criteria['classification'])){
                    if($criteria['classification'][0] > 0){
                        if($where !== ""){ $where .= " AND "; }
                        $where .= 'employee_qualification_option_id in ( ' . implode(",", $criteria['classification']) . ")";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .= ' employee_qualification_option_id = ' . $criteria['classification'];
                }
            }
            
            // EMPLOYEE CODE
            if (isset($criteria['show_employee_code']) && $criteria['show_employee_code']) {
                $header_names['employee_code'] = t('Employee Code');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.employee_code AS employee_code ";
            }
            
            // MODEL
            if (isset($criteria['show_dsd_model']) && $criteria['show_dsd_model']) {
                $header_names['employee_dsdmodel_phrase'] = t('Service Delivery Model');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee_dsdmodel_option.employee_dsdmodel_phrase AS employee_dsdmodel_phrase";
            }
            if (isset($criteria['dsd_model']) && $criteria['dsd_model']) {
                if($criteria['dsd_model'][0] > 0){
                    if($where !== ""){ $where .= " AND "; }
                    $where .= 'link_employee_facility.dsd_model_id IN (' . implode(",", $criteria['dsd_model']) . ")";
                }
            }
            
            // TEAM
            if (isset($criteria['show_dsd_team']) && $criteria['show_dsd_team']) {
                $header_names['employee_dsdteam_phrase'] = t('Service Delivery Team');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee_dsdteam_option.employee_dsdteam_phrase AS employee_dsdteam_phrase ";
            }
            if (isset($criteria['dsd_team']) && $criteria['dsd_team']) {
                if($criteria['dsd_team'][0] > 0){
                    if($where !== ""){ $where .= " AND "; }
                    $where .= 'link_employee_facility.dsd_team_id IN (' . implode(",", $criteria['dsd_team']) . ")";
                }
            }
            
            //PROVINCE
            if (isset($criteria['showProvince']) && $criteria['showProvince']) {
                $header_names['province_name'] = t('Region A (Province)');
                if($select !== ""){ $select .= ", "; }
                $select .= " location_2.location_name AS province_name ";
            }
            if (isset($criteria['province_id']) && count($criteria['province_id']) &&
                !((isset($criteria['district_id']) && count($criteria['district_id']))  ||
                    (isset($criteria['region_c_id']) && count($criteria['region_c_id'])))) {
                if($where !== ""){ $where .= " AND "; }
                $ids = $criteria['province_id'];
                if (is_array($ids)) {
                    if (count($ids) > 1) {
                        $where .= ' location_2.id IN (' . $ids . ')';
                    } else if (count($ids) === 1) {
                        $where .= ' location_2.id = ' . $ids[0];
                    }
                }else {
                    $where .= ' location_2.id = '. $ids;
                }
            }
            
            //DISTRICT
            if (isset($criteria['showDistrict']) && $criteria['showDistrict']) {
                $header_names['district_name'] = t('Region B (Health District)');
                if($select !== ""){ $select .= ", "; }
                $select .= " location_1.location_name AS district_name ";
            }
            if (isset($criteria['district_id']) && count($criteria['district_id']) &&
                !((isset($criteria['region_c_id']) && count($criteria['region_c_id'])))) {
                    if (is_array($criteria['district_id'])) {
                        $ids = array_map(function ($item) {
                            $item = end(explode('_', $item));
                            return $item;
                        }, $criteria['district_id']);
                    }else {
                        $ids = end(explode('_', $criteria['district_id']));
                    }
                    if($where !== ""){ $where .= " AND "; }
                    if (is_array($ids)) {
                    if (count($ids) > 1) {
                        $where .= ' location_1.id IN (' . $ids . ')';
                    } else if (count($ids) === 1) {
                        $where .= ' location_1.id = '. $ids[0];
                    }
                    }else {
                        $where .= ' location_1.id = '. $ids;
                    }
                }
            
            // REGION
            if (isset($criteria['showRegionC']) && $criteria['showRegionC']) {
                $header_names['region_c_name'] = t('Region C (Local Region)');
                if($select !== ""){ $select .= ", "; }
                $select .= " location.location_name AS region_c_name ";
            }
            if (isset($criteria['region_c_id']) && count($criteria['region_c_id'])) {
                if (is_array($criteria['region_c_id'])) {
                    $ids = array_map(function ($item) {
                        $item = end(explode('_', $item));
                        return $item;
                    }, $criteria['region_c_id']);
                }else {
                    $ids = end(explode('_', $criteria['region_c_id']));
                }
                if($where !== ""){ $where .= " AND "; }
                if (is_array($ids)) {
                    if (count($ids) > 1) {
                        $where .= ' location.id IN (' . $ids . ')';
                    } else if (count($ids) === 1) {
                        $where .= ' location.id = '. $ids[0];
                    }
                }else {
                    $where .= ' location.id = '. $ids;
                }
            }
            
            // FACILITY NAME - SITE
            if (isset($criteria['show_facilityInput']) && $criteria['show_facilityInput']) {
                $header_names['facility_name'] =  t('Site Name');
                $header_names['facility_id'] =  t('Site ID');
                if($select !== ""){ $select .= ", "; }
                $select .= " facility.facility_name AS facility_name, facility.id AS facility_id"; //TA:#541
            }
            if (isset($criteria['facilityInput']) && $criteria['facilityInput']) {if(is_array($criteria['facilityInput'])){
                    if($criteria['facilityInput'][0] > 0){
                        if($where !== ""){ $where .= " AND "; }
                        $where .= ' facility.id in ( ' . implode(",", $criteria['facilityInput']) . ")";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .= ' facility.id = ' . $criteria['facilityInput'];
                }
            }
            
            // HOURS WORKED PER WEEK PER SITE
            if ((isset($criteria['show_fte_min']) && $criteria['show_fte_min']) || $criteria['fte_min'] || $criteria['fte_max']) {
                $header_names['hiv_fte_related'] =  t('Hours Worked per Week (per Site)');
                if($select !== ""){ $select .= ", "; }
                $select .= " link_employee_facility.hiv_fte_related AS hiv_fte_related ";
                if($criteria['fte_min']){
                    if($where !== ""){ $where .= " AND "; }
                    $where .= " link_employee_facility.hiv_fte_related > " . $criteria['fte_min'];
                }
                if($criteria['fte_max']){
                    if($where !== ""){ $where .= " AND "; }
                    $where .= " link_employee_facility.hiv_fte_related < " . $criteria['fte_max'];
                }
            }
            
            //MECHANISM NAMES
            if (isset($criteria['show_mechanism_names']) && $criteria['show_mechanism_names']) {
                $header_names['mechanism_phrase'] =  t('Implementing Mechanism Name');
                if($select !== ""){ $select .= ", "; }
                $select .= " mechanism_option.mechanism_phrase AS mechanism_phrase ";
            }
            if((isset($criteria['mechanism_names']) && $criteria['mechanism_names'])){
                if(is_array($criteria['mechanism_names'])){
                    if($criteria['mechanism_names'][0] > 0){
                        if($where !== ""){ $where .= " AND "; }
                        $where .= 'mechanism_option.id in ( ' . implode(",", $criteria['mechanism_names']) . ")";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .= 'mechanism_option.id = '. $criteria['mechanism_names'];
                }
            }
            
            //MECHANISM IDENTIFIER
            if (isset($criteria['show_mechanism_ids']) && show_mechanism_names['show_mechanism_ids']) {
                $header_names['external_id'] =  t('Implementing Mechanism Identifier');
                if($select !== ""){ $select .= ", "; }
                $select .= " mechanism_option.external_id AS external_id ";
            }
            if (isset($criteria['mechanism_ids']) && $criteria['mechanism_ids']){
                if(is_array($criteria['mechanism_ids'])){
                    if($criteria['mechanism_ids'][0] > 0){
                        if($where !== ""){ $where .= " AND "; }
                        $where .= 'mechanism_option.external_id in ( ' . implode(",", $criteria['mechanism_ids']) . ")";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .= 'mechanism_option.external_id = ' . $criteria['mechanism_ids'];
                }
            }
            
          
            //IMPLEMENTING MECHANISM FUNDING END DATE
            if ((isset($criteria['show_mech_fund_date_start']) || $criteria['show_mech_fund_date_start']) ||
                (isset($criteria['mech_fund_date_start']) && $criteria['mech_fund_date_start']) ||
                (isset($criteria['mech_fund_date_end']) && $criteria['mech_fund_date_end'])) {
                    $header_names['mechanism_end_date'] =  t('Implementing Mechanism Funding End Date');
                    if($select !== ""){ $select .= ", "; }
                    $select .= " SUBSTRING_INDEX(mechanism_option.end_date, ' ', 1) AS mechanism_end_date ";
                    if(isset($criteria['mech_fund_date_start']) && $criteria['mech_fund_date_start']){
                        if($where !== ""){ $where .= " AND "; }
                        $d = DateTime::createFromFormat('m/d/Y', $criteria['mech_fund_date_start']);
                        $where .= " mechanism_option.end_date >= '" . $d->format('Y-m-d') . "'";
                    }
                    if(isset($criteria['mech_fund_date_end']) && $criteria['mech_fund_date_end']){
                        if($where !== ""){ $where .= " AND "; }
                        $d = DateTime::createFromFormat('m/d/Y', $criteria['mech_fund_date_end']);
                        $where .= " mechanism_option.end_date <= '" .  $d->format('Y-m-d') . "'";
                    }
                }
             
                //CONTRECT START DATE
            if (isset($criteria['show_contract_start_date_from']) || $criteria['contract_start_date_from'] 
                    || $criteria['contract_start_date_to']) {
                        $header_names['contract_start_date'] =  t('Contract Start Date');
                        if($select !== ""){ $select .= ", "; }
                        $select .= " SUBSTRING_INDEX(employee.agreement_start_date, ' ', 1) AS contract_start_date ";
                        if($criteria['contract_start_date_from']){
                            if($where !== ""){ $where .= " AND "; }
                            $d = DateTime::createFromFormat('m/d/Y', $criteria['contract_start_date_from']);
                            $where .= " employee.agreement_start_date >= '" .  $d->format('Y-m-d') . "'";
                        }
                        if($criteria['contract_start_date_to']){
                            if($where !== ""){ $where .= " AND "; }
                              $d = DateTime::createFromFormat('m/d/Y', $criteria['contract_start_date_to']);
                              $where .= " employee.agreement_start_date <= '" . $d->format('Y-m-d') . "'";
                        } 
            }
            
            //CONTRECT END DATE
            if (isset($criteria['show_contract_end_date_from']) || $criteria['contract_end_date_from']
                || $criteria['contract_end_date_to']) {
                    $header_names['contract_end_date'] =  t('Contract End Date');
                    if($select !== ""){ $select .= ", "; }
                    $select .= " SUBSTRING_INDEX(employee.agreement_end_date, ' ', 1) AS contract_end_date ";
                    if($criteria['contract_end_date_from']){
                        if($where !== ""){ $where .= " AND "; }
                        $d = DateTime::createFromFormat('m/d/Y', $criteria['contract_end_date_from']);
                        $where .= " employee.agreement_end_date >= '" .  $d->format('Y-m-d') . "'";
                    }
                    if($criteria['contract_end_date_to']){
                        if($where !== ""){ $where .= " AND "; }
                        $d = DateTime::createFromFormat('m/d/Y', $criteria['contract_end_date_to']);
                        $where .= " employee.agreement_end_date <= '" . $d->format('Y-m-d'). "'";
                    }
                }
                
             //INTENDENT TRANSITION   
            if (isset($criteria['show_intended_transition']) && $criteria['show_intended_transition']) {
                $header_names['intended_transition'] =  t('Intended Transition');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee_transition_option.transition_phrase AS intended_transition ";
            }
            if (isset($criteria['intended_transition']) && $criteria['intended_transition']) {
                if(is_array($criteria['intended_transition'])){
                    if($criteria['intended_transition'][0] > 0){
                        if($where !== ""){ $where .= " AND "; }
                        $where .= ' employee.employee_transition_option_id in ( ' . implode(",", $criteria['intended_transition']) . ")";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .= ' employee.employee_transition_option_id = ' . $criteria['intended_transition'];
                }
            }
            
            //INTENDENT TRANSITION OTHER
            if (isset($criteria['show_intended_transition_other']) && $criteria['show_intended_transition_other']) {
                $header_names['transition_other'] =  t('Intended Transition Other');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.transition_other AS transition_other ";
            }
            if (isset($criteria['intended_transition_other']) && $criteria['intended_transition_other']) {
                if(is_array($criteria['intended_transition_other'])){
                    if($criteria['intended_transition_other'][0] !== '0'){
                        if($where !== ""){ $where .= " AND "; }
                        $where .= ' employee.transition_other in ( ' . "'" . implode("','", $criteria['intended_transition_other']) . "')";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .= " employee.transition_other = '" . $criteria['intended_transition_other'] . "'";
                }
            }
            
            //INTENDENT TRANSITION DATE
            if (isset($criteria['show_intended_transition_start_date']) || 
                $criteria['intended_transition_start_date'] || $criteria['intended_transition_end_date']) {
                    $header_names['transition_date'] =  t('Intended Transition Date');
                    if($select !== ""){ $select .= ", "; }
                    $select .= " SUBSTRING_INDEX(employee.transition_date, ' ', 1) AS transition_date ";
                    if($criteria['intended_transition_start_date']){
                        if($where !== ""){ $where .= " AND "; }
                        $d = DateTime::createFromFormat('m/d/Y', $criteria['intended_transition_start_date']);
                        $where .= " employee.transition_date >= '" . $d->format('Y-m-d') . "'";
                    }
                    if($criteria['intended_transition_end_date']){
                        if($where !== ""){ $where .= " AND "; }
                        $d = DateTime::createFromFormat('m/d/Y', $criteria['intended_transition_end_date']);
                        $where .= " employee.transition_date <= '" . $d->format('Y-m-d') . "'";
                    }
            }
            
            //transition outcome
            if (isset($criteria['show_actual_transition']) && $criteria['show_actual_transition']) {
                $header_names['actual_transition'] =  t('Actual Transition Outcome');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee_transition_complete_option.transition_complete_phrase AS actual_transition ";
            }
            if (isset($criteria['actual_transition']) && $criteria['actual_transition']) {
                if(is_array($criteria['actual_transition'])){
                    if($criteria['actual_transition'][0] > 0){
                        if($where !== ""){ $where .= " AND "; }
                        $where .=' employee_transition_complete_option_id in ( ' . implode(",", $criteria['actual_transition']) . ")";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .=' employee_transition_complete_option_id = ' . $criteria['actual_transition'];
                }
            }
            
            //transition outcome OTHER
            if (isset($criteria['show_actual_transition_other']) && $criteria['show_actual_transition_other']) {
                $header_names['transition_complete_other'] =  t('Actual Transition Outcome, Other');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.transition_complete_other AS transition_complete_other ";
            }
            if (isset($criteria['actual_transition_other']) && $criteria['actual_transition_other']) {
                if(is_array($criteria['actual_transition_other'])){
                    if($criteria['actual_transition_other'][0] !== '0'){
                        if($where !== ""){ $where .= " AND "; }
                        $where .=' employee.transition_complete_other in ( ' . "'" . implode("','", $criteria['actual_transition_other']) . "')";
                    }
                }else{
                    if($where !== ""){ $where .= " AND "; }
                    $where .=" employee.transition_complete_other = '" . $criteria['actual_transition_other'] . "'";
                }
            }
            
            //transition outcome DATE
            if (isset($criteria['show_transition_start_date']) && $criteria['show_transition_start_date']) {
                $header_names['transition_complete_date'] =  t('Actual Transition Date');
                if($select !== ""){ $select .= ", "; }
                $select .= " SUBSTRING_INDEX(employee.transition_complete_date, ' ', 1) AS transition_complete_date ";
            }
            if (isset($criteria['transition_start_date']) && $criteria['transition_start_date']) {
                if($where !== ""){ $where .= " AND "; }
                $d = DateTime::createFromFormat('m/d/Y', $criteria['transition_start_date']);
                $where .=" (transition_complete_date >= '" . $d->format('Y-m-d') . "' OR transition_complete_date like '0000-00-00%' OR transition_complete_date IS NULL) ";//TA:#511
            }
            if (isset($criteria['transition_end_date']) && $criteria['transition_end_date']) {
                if($where !== ""){ $where .= " AND "; }
                $d = DateTime::createFromFormat('m/d/Y', $criteria['transition_end_date']);
                $where .=" transition_complete_date <= '" . $d->format('Y-m-d'). "'";
            }
            
            // STIPEND or SALARY
            if (isset($criteria['show_salary_or_stipend'])) {
                $header_names['salary_or_stipend'] =  t('Salaried or Stipend');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.salary_or_stipend AS salary_or_stipend ";
            }
            if(isset($criteria['salary_or_stipend'])) {
                if($where !== ""){ $where .= " AND "; }
                $where .= " salary_or_stipend='" . $criteria['salary_or_stipend'] . "'";
            }
            
            // hours
            if (isset($criteria['show_hours_min']) && $criteria['show_hours_min']) {
                $header_names['funded_hours_per_week'] =  t('Hours Worked per Week (FTE)');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.funded_hours_per_week AS funded_hours_per_week ";
            }
            if (isset($criteria['hours_min']) && intval($criteria['hours_min']) >= 0) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' funded_hours_per_week >= ' . intval($criteria['hours_min']);
            }
            if (isset($criteria['hours_max']) && $criteria['hours_max']) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' funded_hours_per_week <= ' . intval($criteria['hours_max']);
            }
            
            // salary
            if (isset($criteria['show_salary_min']) && $criteria['show_salary_min']) {
                $header_names['salary'] =  t('Salary (R)');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.salary AS salary ";
            }
            if (isset($criteria['salary_min']) && intval($criteria['salary_min']) >= 0) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' salary >=' . intval($criteria['salary_min']);
            }
            if (isset($criteria['salary_max']) && $criteria['salary_max']) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' salary <= ' . intval($criteria['salary_max']);
            }
            
            // benefits
            if (isset($criteria['show_benefits_min']) && $criteria['show_benefits_min']) {
                $header_names['benefits'] =  t('Financial Benefits (R)');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.benefits AS benefits ";
            }
            if (isset($criteria['benefits_min']) && intval($criteria['benefits_min']) >= 0) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' benefits >=' . intval($criteria['benefits_min']);
            }
            if (isset($criteria['benefits_max']) && $criteria['benefits_max']) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' benefits <= ' . intval($criteria['benefits_max']);
            }
            
            //benefits description
            if ((isset($criteria['show_employee_financial_benefits_description']) && $criteria['show_employee_financial_benefits_description']) ||
                (isset($criteria['employee_financial_benefits_description']) && $criteria['employee_financial_benefits_description'])) {
                   $more_join .= "  
LEFT JOIN employee_to_financial_benefits_description_option ON employee_to_financial_benefits_description_option.employee_id = employee.id 
LEFT JOIN employee_financial_benefits_description_option ON employee_financial_benefits_description_option.id = employee_to_financial_benefits_description_option.employee_financial_benefits_description_option_id ";       
                   if (isset($criteria['show_employee_financial_benefits_description']) && $criteria['show_employee_financial_benefits_description']){
                       $header_names['financial_benefits_description_option'] =  t('Financial Benefits Description');
                       if($select !== ""){ $select .= ", "; }
                       $select .= " GROUP_CONCAT(DISTINCT  employee_financial_benefits_description_option.financial_benefits_description_option) as financial_benefits_description_option ";
                   }
                   if (isset($criteria['employee_financial_benefits_description']) && $criteria['employee_financial_benefits_description']){
                       if(is_array($criteria['employee_financial_benefits_description'])){
                           if($criteria['employee_financial_benefits_description'][0] > 0){
                               if($where !== ""){ $where .= " AND "; }
                               $where .=' employee_to_financial_benefits_description_option.employee_financial_benefits_description_option_id in ( ' . implode(",", $criteria['employee_financial_benefits_description']) . ")";
                           }
                       }else{
                           if($where !== ""){ $where .= " AND "; }
                           $where .=' employee_to_financial_benefits_description_option.employee_financial_benefits_description_option_id =' . $criteria['employee_financial_benefits_description'];
                       }
                   }
                }
            
                //non financial benefits
            if (isset($criteria['show_non_financial_benefits_min']) && $criteria['show_non_financial_benefits_min']) {
                $header_names['non_financial_benefits'] =  t('Non-financial Benefits (R)');
                if($select !== ""){ $select .= ", "; }
                $select .= "employee.non_financial_benefits AS non_financial_benefits ";
            }
            if (isset($criteria['non_financial_benefits_min']) && intval($criteria['non_financial_benefits_min']) >= 0) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' non_financial_benefits >= ' . intval($criteria['non_financial_benefits_min']);
            }
            if (isset($criteria['non_financial_benefits_max']) && $criteria['non_financial_benefits_max']) {
                if($where !== ""){ $where .= " AND "; }
                $where .=' non_financial_benefits <=' . intval($criteria['non_financial_benefits_max']);
            }
            
            //non financial benefits description
            if ((isset($criteria['show_employee_non_financial_benefits_description']) && $criteria['show_employee_non_financial_benefits_description']) ||
                (isset($criteria['employee_non_financial_benefits_description']) && $criteria['employee_non_financial_benefits_description'])) {
                    $more_join .= "
 LEFT JOIN employee_to_non_financial_benefits_description_option ON employee_to_non_financial_benefits_description_option.employee_id = employee.id 
 LEFT JOIN employee_non_financial_benefits_description_option ON employee_non_financial_benefits_description_option.id = employee_to_non_financial_benefits_description_option.employee_non_financial_benefits_description_option_id  
 ";
                    if (isset($criteria['show_employee_non_financial_benefits_description']) && $criteria['show_employee_non_financial_benefits_description']){
                        $header_names['non_financial_benefits_description_option'] =  t('Non-financial Benefits Description');
                        if($select !== ""){ $select .= ", "; }
                        $select .=  ' GROUP_CONCAT(DISTINCT employee_non_financial_benefits_description_option.non_financial_benefits_description_option) as non_financial_benefits_description_option ';
                    }
                    if (isset($criteria['employee_non_financial_benefits_description']) && $criteria['employee_non_financial_benefits_description']){
                        if(is_array($criteria['employee_non_financial_benefits_description'])){
                            if($criteria['employee_non_financial_benefits_description'][0] > 0){
                                if($where !== ""){ $where .= " AND "; }
                                $where .= ' employee_to_non_financial_benefits_description_option.employee_non_financial_benefits_description_option_id in ( ' . implode(",", $criteria['employee_non_financial_benefits_description']) . ")";
                            }
                        }else{
                            if($where !== ""){ $where .= " AND "; }
                            $where .=' employee_to_non_financial_benefits_description_option.employee_non_financial_benefits_description_option_id =' . $criteria['employee_non_financial_benefits_description'];
                        }
                    }
                }
            
             //   professional development
            if (isset($criteria['show_professional_development_min']) && $criteria['show_professional_development_min']) {
                $header_names['professional_development'] =  t('Professional Development (R)');
                if($select !== ""){ $select .= ", "; }
                $select .= "employee.professional_development AS professional_development ";
            }
            if (isset($criteria['professional_development_min']) && intval($criteria['professional_development_min']) >= 0) {
                if($where !== ""){ $where .= " AND "; }
                $where .='professional_development >= ' . intval($criteria['professional_development_min']);
            }
            if (isset($criteria['professional_development_max']) && $criteria['professional_development_max']) {
                if($where !== ""){ $where .= " AND "; }
                $where .='professional_development <= ' . intval($criteria['professional_development_max']);
            }
            
             //professional development description
            if ((isset($criteria['show_employee_professional_development_description']) && $criteria['show_employee_professional_development_description']) ||
                (isset($criteria['employee_professional_development_description']) && $criteria['employee_professional_development_description'])) {
                    $more_join .="
 LEFT JOIN employee_to_professional_development_description_option ON employee_to_professional_development_description_option.employee_id = employee.id 
 LEFT JOIN employee_professional_development_description_option ON employee_professional_development_description_option.id = employee_to_professional_development_description_option.employee_professional_development_description_option_id
";
                    if (isset($criteria['show_employee_professional_development_description']) && $criteria['show_employee_professional_development_description']){
                        $header_names['professional_development_description_option'] =  t('Professional Development Description');
                        if($select !== ""){ $select .= ", "; }
                        $select .= " GROUP_CONCAT(DISTINCT employee_professional_development_description_option.professional_development_description_option) as professional_development_description_option ";
                    }
                    if (isset($criteria['employee_professional_development_description']) && $criteria['employee_professional_development_description']){
                        if(is_array($criteria['employee_professional_development_description'])){
                            if($criteria['employee_professional_development_description'][0] > 0){
                                if($where !== ""){ $where .= " AND "; }
                                $where .='employee_to_professional_development_description_option.employee_professional_development_description_option_id in ( ' . implode(",", $criteria['employee_professional_development_description']) . ")";
                            }
                        }else{
                            if($where !== ""){ $where .= " AND "; }
                            $where .='employee_to_professional_development_description_option.employee_professional_development_description_option_id = ' . $criteria['employee_professional_development_description'];
                        }
                    }
                }
                
            // stipend min
            if (isset($criteria['show_stipend_min']) && $criteria['show_stipend_min']) {
                $header_names['stipend'] =  t('Stipend (R)');
                if($select !== ""){ $select .= ", "; }
                $select .= "employee.stipend AS stipend ";
            }
            if (isset($criteria['stipend_min']) && $criteria['stipend_min'] >= 0) {
                if($where !== ""){ $where .= " AND "; }
                $where .= ' stipend >= ' . intval($criteria['stipend_min']);
            }
            if (isset($criteria['stipend_max']) && $criteria['stipend_max']) {
                if($where !== ""){ $where .= " AND "; }
                $where .= ' stipend <= ' . intval($criteria['stipend_max']);
            }
            
            //COST MIN
            if (isset($criteria['show_cost_min']) && $criteria['show_cost_min']) {
                $header_names['annual_cost'] =  t('Total Cost for Quarter');
                if($select !== ""){ $select .= ", "; }
                $select .= "employee.annual_cost AS annual_cost ";
            }
            if (isset($criteria['cost_min']) && $criteria['cost_min'] >= 0) {
                if($where !== ""){ $where .= " AND "; }
                $where .= ' annual_cost >= ' . intval($criteria['cost_min']);
            }
            if (isset($criteria['cost_max']) && $criteria['cost_max']) {
                if($where !== ""){ $where .= " AND "; }
                $where .= ' annual_cost <= ' . intval($criteria['cost_max']);
            }
            
            //AGENCY
            if(isset($criteria['show_agencies']) && $criteria['show_agencies']){
                    $header_names['funder_phrase'] =  t('Implementing Agency');
                    if($select !== ""){ $select .= ", "; }
                    $select .= "partner_funder_option.funder_phrase AS funder_phrase ";
            }
            if (isset($criteria['agencies']) && $criteria['agencies']){
                    if(is_array($criteria['agencies'])){
                        if($criteria['agencies'][0] > 0){
                            if($where !== ""){ $where .= " AND "; }
                            $where .= ' mechanism_option.funder_id in ( ' . implode(",", $criteria['agencies']) . ")";
                        }
                    }else{
                        if($where !== ""){ $where .= " AND "; }
                        $where .= ' mechanism_option.funder_id = '. $criteria['agencies'];
                    }
             }
            
            //MECHANISM PARTNERS
            if ((isset($criteria['show_mech_partners']) && $criteria['show_mech_partners']) || (isset($criteria['mech_partners']) && $criteria['mech_partners'])) {
                $header_names['impl_mech_partner_name'] =  t('Implementing Mechanism Prime Partner Name');
                if($select !== ""){ $select .= ", "; }
                $select .= " partner_1.partner AS impl_mech_partner_name ";
                if (isset($criteria['mech_partners']) && $criteria['mech_partners']){
                    if(is_array($criteria['mech_partners'])){
                        if($criteria['mech_partners'][0] > 0){
                            if($where !== ""){ $where .= " AND "; }
                            $where .= ' mechanism_option.owner_id in ( ' . implode(",", $criteria['mech_partners']) . ")";
                        }
                    }else{
                        if($where !== ""){ $where .= " AND "; }
                        $where .= ' mechanism_option.owner_id = ' . $criteria['mech_partners'];
                    }
                }
            }
            
            if (isset($criteria['show_timestamp_created_start_date']) && $criteria['show_timestamp_created_start_date']) {
                $header_names['timestamp_created'] =  t('Timestamp Created');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.timestamp_created ";
            }
            if (isset($criteria['show_timestamp_updated_start_date']) && $criteria['show_timestamp_updated_start_date']) {
                $header_names['timestamp_updated'] =  t('Timestamp Updated');
                if($select !== ""){ $select .= ", "; }
                $select .= " employee.timestamp_updated ";
            }
            $this->view->assign('headers', $header_names);
           
            if($select === ""){
                $status = ValidationContainer::instance();
                $status->setStatusMessage(t('Error: Please select criteria to show.'));
            }else{
                $select = "SELECT " . $select;
                $select .= "
FROM
	(
		(
			employee_dsdteam_option
		RIGHT JOIN(
			(
				(
					(
						(
							facility
						RIGHT JOIN(
							link_employee_facility
						RIGHT JOIN(
							employee_transition_complete_option
						RIGHT JOIN(
							employee_transition_option
						RIGHT JOIN(
							(
								employee_qualification_option
							RIGHT JOIN(
								partner
							LEFT JOIN employee ON partner.id = employee.partner_id
							)ON employee_qualification_option.id = employee.employee_qualification_option_id
							)
						LEFT JOIN employee_role_option ON employee.employee_role_option_id = employee_role_option.id
						)ON employee_transition_option.id = employee.employee_transition_option_id
						)ON employee_transition_complete_option.id = employee.employee_transition_complete_option_id
						)ON link_employee_facility.employee_id = employee.id
						)ON facility.id = link_employee_facility.facility_id
						)
					LEFT JOIN location ON facility.location_id = location.id
					)
				LEFT JOIN location AS location_1 ON location.parent_id = location_1.id
				)
			LEFT JOIN location AS location_2 ON location_1.parent_id = location_2.id
			)
		LEFT JOIN employee_dsdmodel_option ON link_employee_facility.dsd_model_id = employee_dsdmodel_option.id
		)ON employee_dsdteam_option.id = link_employee_facility.dsd_team_id
		)
	LEFT JOIN(
		partner AS partner_1
	RIGHT JOIN mechanism_option ON partner_1.id = mechanism_option.owner_id
	)ON link_employee_facility.mechanism_option_id = mechanism_option.id
	)
LEFT JOIN partner_funder_option ON mechanism_option.funder_id = partner_funder_option.id ";

//TA:#511 new version display prime partners and subpartners 
if (!$this->hasACL('mechanism_option_all')) {
       if($where !== ""){ $where .= " AND "; }
       //   old version display only prime partners, no subpartners 
       //$where .= " partner.id in (select id from partner where id in (select owner_id from mechanism_option where id in (select mechanism_option_id from user_to_mechanism_access where user_id=" . $this->isLoggedIn() . ")))        ";
       $where .= " ( partner.id in (select id from partner where id in (select owner_id from mechanism_option where id in (select mechanism_option_id from user_to_mechanism_access where user_id=" . $this->isLoggedIn() . "))) ";
       $where .= " OR partner.id IN (select subpartner.id FROM partner
           LEFT JOIN mechanism_option ON partner.id = mechanism_option.owner_id 
           LEFT JOIN link_mechanism_partner ON link_mechanism_partner.mechanism_option_id = mechanism_option.id 
           LEFT JOIN partner AS subpartner ON link_mechanism_partner.partner_id = subpartner.id AND link_mechanism_partner.partner_id <> mechanism_option.owner_id
           WHERE mechanism_option.id IN (SELECT mechanism_option_id FROM user_to_mechanism_access WHERE user_id = " . $this->isLoggedIn() . ") AND subpartner.id IS NOT NULL )) ";
}else{
      if (!$this->hasACL('training_organizer_option_all')) {
                            if($where !== ""){ $where .= " AND "; }
                            $where .= "
    partner.id  in (
    select id from partner where partner.organizer_option_id in
    (select training_organizer_option_id from user_to_organizer_access where user_to_organizer_access.user_id=" . $this->isLoggedIn() . ")) ";
                        }
}
                
if($more_join !== ""){
    $select = $select . " " . $more_join . " ";
}
                
if($where !== ""){
     $select = $select . " WHERE " . $where ;
}

if($group !== ""){
    $select = $select . " GROUP BY " . $group ;
}

if($order !== ""){
    $select = $select . " ORDER BY " . $order ;
}

//print $select;//TA:1000
                $this->view->assign('output',$db->fetchAll($select));
            }
        }
        
        $choose = array("0" => '--' . t("All") . '--');
        
        $select = $db->select()
        ->from('partner', array('id', 'partner'))
        ->order('partner ASC');
        
        if (!$this->hasACL('training_organizer_option_all')) {
            $uid = $this->isLoggedIn();
            $select->join(array('user_to_organizer_access'),
                'user_to_organizer_access.training_organizer_option_id = partner.organizer_option_id', array())
                ->where('user_to_organizer_access.user_id = ?', $uid);
        }
        
        $partners = $choose + $db->fetchPairs($select);
        
        //TA:#510
        $select = $db->select()
        ->from('partner', array('id', 'partner'))
        ->order('partner ASC');  
        if (!$this->hasACL('training_organizer_option_all')) {
            $uid = $this->isLoggedIn();
            $select->join(array('user_to_organizer_access'),
                'user_to_organizer_access.training_organizer_option_id = partner.organizer_option_id', array())
                ->where('user_to_organizer_access.user_id = ?', $uid);
        } 
        $select = "
select partner_impl.id as id, partner_impl.partner, GROUP_CONCAT(DISTINCT partner.id) as owner_id from employee
left join partner on partner.id=employee.partner_id
left join link_employee_facility on link_employee_facility.employee_id=employee.id
left join mechanism_option on mechanism_option.id=link_employee_facility.mechanism_option_id
left join partner as partner_impl on partner_impl.id=mechanism_option.owner_id ";
        if (!$this->hasACL('training_organizer_option_all')) {
            $select .= " left join user_to_organizer_access on user_to_organizer_access.training_organizer_option_id = partner_impl.organizer_option_id ";
        }
        $select .=" where partner_impl.id is not null ";
        if (!$this->hasACL('training_organizer_option_all')) {
            $select .= " AND user_to_organizer_access.user_id=" . $this->isLoggedIn();
        }
        $select .= " group by partner_impl.id order by partner_impl.partner";
        $mech_partners = $db->fetchAll($select);
       // print_r($mech_partners);
        //
        
        $facilities = $choose + $db->fetchPairs($db->select()
            ->from('facility', array('id', 'facility_name'))
            ->order('facility_name ASC')
            );
        
        $facilityTypes = $choose + $db->fetchPairs($db->select()
            ->from('facility_type_option', array('id', 'facility_type_phrase'))
            ->order('facility_type_phrase ASC')
            );
        
        $classifications = $choose + $db->fetchPairs($db->select()
            ->from('employee_qualification_option', array('id', 'qualification_phrase'))
            ->order('qualification_phrase ASC')
            );
        
        //TA:#419
        $employee_codes = $choose + $db->fetchPairs($db->select()
            ->from('employee', array('employee_code', 'employee_code'))
            ->order('employee_code ASC')
            );
        
        //TA:#419
        $dsd_models = $choose + $db->fetchPairs($db->select()
            ->from('employee_dsdmodel_option', array('id', 'employee_dsdmodel_phrase'))
            ->order('employee_dsdmodel_phrase ASC')
            );
        
        //TA:#419
        $dsd_teams = $choose + $db->fetchPairs($db->select()
            ->from('employee_dsdteam_option', array('id', 'employee_dsdteam_phrase'))
            ->order('employee_dsdteam_phrase ASC')
            );
        
        $roles = $choose + $db->fetchPairs($db->select()
            ->from('employee_role_option', array('id', 'role_phrase'))
            ->order('role_phrase ASC')
            );
        
        $transitions = $choose + $db->fetchPairs($db->select()
            ->from('employee_transition_option', array('id', 'transition_phrase'))
            ->order('transition_phrase ASC')
            );
        
        //TA:#419
        $transitions_other = $choose + $db->fetchPairs($db->select()
            ->from('employee', array('transition_other', 'transition_other'))
            ->group('transition_other')
            ->where('transition_other is not null')
            ->order('transition_other ASC')
            );
        $transitions_complete = $choose + $db->fetchPairs($db->select()
            ->from('employee_transition_complete_option', array('id', 'transition_complete_phrase'))
            ->order('transition_complete_phrase ASC'));
        $transitions_complete_other = $choose + $db->fetchPairs($db->select()
            ->from('employee', array('transition_complete_other', 'transition_complete_other'))
            ->group('transition_complete_other')
            ->where('transition_complete_other is not null')
            ->order('transition_complete_other ASC')
            );
        $agencies = $choose + $db->fetchPairs($db->select()
            ->from('partner_funder_option', array('id', 'funder_phrase'))
            ->order('funder_phrase ASC'));
        //TA:#510
        $mechanism_ids = $db->fetchAll($db->select()
                    ->from('mechanism_option', array('external_id','GROUP_CONCAT(DISTINCT owner_id) as owner_id'))
                    ->group('external_id')
                    ->where('external_id is not null')
                    ->order('external_id ASC')
                    );
        //TA:#510
        $mechanism_names = $db->fetchAll($db->select()
            ->from('mechanism_option', array('id', 'mechanism_phrase', 'owner_id'))
            ->order('mechanism_phrase ASC'));
        
        $bases = $choose + $db->fetchPairs($db->select()
            ->from('employee_base_option', array('id', 'base_phrase'))
            ->order('base_phrase ASC')
            );
        
        $this->view->assign('partners', $partners);
        $this->view->assign('mech_partners', $mech_partners);//TA:#510
        
        //TA:#466
        $select = $db->select()->from('employee_financial_benefits_description_option', array('id', 'financial_benefits_description_option'))->order('financial_benefits_description_option ASC');
        $this->view->assign('employee_financial_benefits_description', $choose + $db->fetchPairs($select));
        ///
        //TA:#468
        $select = $db->select()->from('employee_non_financial_benefits_description_option', array('id', 'non_financial_benefits_description_option'))->order('non_financial_benefits_description_option ASC');
        $this->view->assign('employee_non_financial_benefits_description', $choose + $db->fetchPairs($select));
        ///
        //TA:#474
        $select = $db->select()->from('employee_professional_development_description_option', array('id', 'professional_development_description_option'))->order('professional_development_description_option ASC');
        $this->view->assign('employee_professional_development_description', $choose + $db->fetchPairs($select));
        ///
        $this->view->assign('facilities', $facilities);
        $this->view->assign('facilityTypes', $facilityTypes);
        $this->view->assign('classifications', $classifications);
        $this->view->assign('employee_codes', $employee_codes); //TA:#419
        $this->view->assign('dsd_models', $dsd_models); //TA:#419
        $this->view->assign('dsd_teams', $dsd_teams); //TA:#419
        $this->view->assign('roles', $roles);
        $this->view->assign('transitions', $transitions);
        $this->view->assign('transitions_other', $transitions_other);//TA:#419
        $this->view->assign('transitions_complete', $transitions_complete); //TA:#419
        $this->view->assign('transitions_complete_other', $transitions_complete_other);//TA:#419
        $this->view->assign('agencies', $agencies);//TA:#419
        $this->view->assign('mechanism_ids', $mechanism_ids);//TA:#419
        $this->view->assign('mechanism_names', $mechanism_names);//TA:#419
        $this->view->assign('locations', $locations);
        $this->view->assign('bases', $bases);
        
        //TA:#293 set location multiple selection
        require_once ('views/helpers/Location.php');
        $criteria['district_id'] = regionFiltersGetDistrictIDMultiple($criteria);
        $criteria['region_c_id'] = regionFiltersGetLastIDMultiple('', $criteria);
        
        //TA:#293.1
        //         $helper = new Helper();
        //         $this->viewAssignEscaped('sites', $helper->getFacilities());
        
      
        $this->view->assign('quarter', $this->getCurrentQuarter());//TA:#513
        $this->view->assign('prev_quarter_start_date', $this->getPreviousQuarterStartDate());//TA:#511
        $this->view->assign('prev_quarter_end_date', $this->getPreviousQuarterEndDate());//TA:#511
        
        $this->view->assign('criteria', $criteria);
        
    }
    

    /**
     * @param $rows - reference to database roles returned by filtered query
     * @param $column - the database column to use for data collection
     * @param bool $regional - whether results should be organized by province or not
     * @return array - nested array with processed data and totals
     */

    protected function organizeEmployeeResults(&$rows, $column, $regional = false) {
        $allData = array('fulltimecount' => 0, 'parttimecount' => 0, 'cost' => 0, 'rows' => $rows);

        $allData['byPartner'] = array();
        $byPartner = &$allData['byPartner'];

        $allData[$column] = array();
        $byColumn = &$allData[$column];

        $allData['byProvince'] = array();
        $byProvince = &$allData['byProvince'];

        foreach($rows as $r) {

            // post-process the data into a more useful model for the report and add up the costs.
            $partner = $r['partner'] ? $r['partner'] : 'unknown partner';
            $province = $r['province_name'] ? $r['province_name'] : 'unknown province';
            $key = $r[$column] ? $r[$column] : 'unknown ' . $column;

            $allData['fulltimecount'] += $r['fulltimecount'];
            $allData['parttimecount'] += $r['parttimecount'];
            $allData['cost'] += $r['cost'];

            if ($regional) {
                // by Province
                if (!array_key_exists($province, $byProvince)) {
                    $byProvince[$province] = array(
                        'fulltimecount' => 0,
                        'parttimecount' => 0,
                        'cost' => 0,
                        'rows' => array(),
                        'byPartner' => array(),
                        $column => array(),
                    );
                }
                $p = &$byProvince[$province];
                $p['rows'][] = &$r;

                $p['fulltimecount'] += $r['fulltimecount'];
                $p['parttimecount'] += $r['parttimecount'];
                $p['cost'] += $r['cost'];

                // get partner province totals
                $p = &$byProvince[$province]['byPartner'];
                if (!array_key_exists($partner, $p)) {
                    $p[$partner] = array(
                        'fulltimecount' => 0,
                        'parttimecount' => 0,
                        'cost' => 0,
                        $column => array(),
                        'rows' => array()
                    );
                }
                $p = &$p[$partner];
                $p['rows'][] = $r;

                $p['fulltimecount'] += $r['fulltimecount'];
                $p['parttimecount'] += $r['parttimecount'];
                $p['cost'] += $r['cost'];

                $p = &$byProvince[$province]['byPartner'][$partner][$column];
                if (!array_key_exists($key, $p)) {
                    $p[$key] = $r;
                }

                // organize by category per province
                $p = &$byProvince[$province][$column];
                if (!array_key_exists($key, $p)) {
                    $p[$key] = array(
                        'fulltimecount' => 0,
                        'parttimecount' => 0,
                        'cost' => 0,
                        'rows' => array()
                    );
                }
                $p = &$p[$key];

                $p['rows'][] = $r;
                $p['fulltimecount'] += $r['fulltimecount'];
                $p['parttimecount'] += $r['parttimecount'];
                $p['cost'] += $r['cost'];
            }

            // by partner
            if (!array_key_exists($partner, $byPartner)) {
                $byPartner[$partner] = array(
                    'fulltimecount' => 0,
                    'parttimecount' => 0,
                    'cost' => 0,
                    'rows' => array()
                );
                $byPartner[$partner][$column] = array();
            }
            $p = &$byPartner[$partner];
            $p['fulltimecount'] += $r['fulltimecount'];
            $p['parttimecount'] += $r['parttimecount'];
            $p['cost'] += $r['cost'];
            $p['rows'][] = $r;

            // by province partner category
            if (!array_key_exists($key, $p[$column])) {
                $p[$column][$key] = array(
                    'fulltimecount' => 0,
                    'parttimecount' => 0,
                    'cost' => 0,
                    'rows' => array()
                );
            }
            $p = &$p[$column][$key];
            $p['fulltimecount'] += $r['fulltimecount'];
            $p['parttimecount'] += $r['parttimecount'];
            $p['cost'] += $r['cost'];
            $p['rows'][] = $r;

            // by column totals
            $p = &$byColumn;
            if (!array_key_exists($key, $p)) {
                $p[$key] = array(
                    'fulltimecount' => 0,
                    'parttimecount' => 0,
                    'cost' => 0,
                    'rows' => array()
                );
            }
            $p = &$p[$key];
            $p['fulltimecount'] += $r['fulltimecount'];
            $p['parttimecount'] += $r['parttimecount'];
            $p['cost'] += $r['cost'];
            $p['rows'][] = $r;
        }
        return ($allData);
    }

    /**
     * creates an array of output rows for report tables
     * relies on incoming data rows to be ordered by province and by partner
     *
     * @param $data - a data structure processed by organizeEmployeeResults
     * @param $column - the column to group by
     * @param $columnValues - the possible values for the columns, can vary by region
     * @param $partners - a list of partners for the table, cannot vary per region
     * @param bool $regional - whether to sort by region also
     * @return array
     */
    protected function formatEmployeeResultsForTable(&$data, $column, $columnValues, $partners, $regional = false) {

        if ($regional) {
            $outputRows = array();
            $numPartnerColumns = (count($data['byPartner']) + 1) * 3 + 1;
            foreach ($data['byProvince'] as $province => $provinceData) {

                $outputRows[] = array_pad(array($province), $numPartnerColumns, ' ');

                $outputRows = array_merge($outputRows,
                    $this->formatEmployeeResultsForTable($provinceData, $column, array_keys($provinceData[$column]), $partners, false));

                $lastRow = count($outputRows) - 1;
                $outputRows[$lastRow][0] = $province . ' ' . t('Total');
            }
            $grandTotals = array(t('Grand Total'));
            foreach ($data['byPartner'] as $partner => $partnerValues) {
                array_push($grandTotals, number_format($partnerValues['fulltimecount']),
                    number_format($partnerValues['parttimecount']), number_format($partnerValues['cost']));
            }
            array_push($grandTotals, number_format($data['fulltimecount']), number_format($data['parttimecount']),
                number_format($data['cost']));
            $outputRows[] = $grandTotals;
        }
        else {
            $outputRows = array_combine(array_flip($columnValues), array_map(function($v) { return array($v); }, $columnValues));
            $outputRows[] = array(t('Totals'));
            $lastRow = count($outputRows) - 1;
            $columnIndexes = array_flip($columnValues);

            // partner category totals
            foreach ($partners as $partner) {
                foreach ($columnValues as $v) {
                    if (array_key_exists($partner, $data['byPartner']) && array_key_exists($v, $data['byPartner'][$partner][$column])) {
                        array_push($outputRows[$columnIndexes[$v]], number_format($data['byPartner'][$partner][$column][$v]['fulltimecount']),
                            number_format($data['byPartner'][$partner][$column][$v]['parttimecount']),
                            number_format($data['byPartner'][$partner][$column][$v]['cost']));
                    }
                    else {
                        array_push($outputRows[$columnIndexes[$v]], 0, 0, 0);
                    }
                }

                if (array_key_exists($partner, $data['byPartner'])) {
                    array_push($outputRows[$lastRow], number_format($data['byPartner'][$partner]['fulltimecount']),
                        number_format($data['byPartner'][$partner]['parttimecount']),
                        number_format($data['byPartner'][$partner]['cost']));
                }
                else {
                    array_push($outputRows[$lastRow], 0, 0, 0);
                }

            }

            array_push($outputRows[$lastRow],  number_format($data['fulltimecount']),
                number_format($data['parttimecount']),
                number_format($data['cost']));

            // category totals
            foreach ($columnValues as $v) {
                if (array_key_exists($v, $data[$column])) {
                    array_push($outputRows[$columnIndexes[$v]], number_format($data[$column][$v]['fulltimecount']),
                        number_format($data[$column][$v]['parttimecount']),
                        number_format($data[$column][$v]['cost']));
                }
                else {
                    array_push($outputRows[$columnIndexes[$v]], 0, 0, 0);
                }
            }
        }
        return $outputRows;
    }

	public function partnersAction() {
		require_once ('models/table/Helper.php');
		require_once ('views/helpers/FormHelper.php');
		require_once ('views/helpers/DropDown.php');
		require_once ('views/helpers/Location.php');
		require_once ('views/helpers/CheckBoxes.php');
		require_once ('views/helpers/TrainingViewHelper.php');

		$criteria = $this->getAllParams();

		if ($criteria['go'])
		{

			$where = array();
			$criteria['last_selected_rgn'] = regionFiltersGetLastID('', $criteria);
			list($a, $location_tier, $location_id) = $this->getLocationCriteriaValues($criteria);
			list($locationFlds, $locationsubquery) = Location::subquery($this->setting('num_location_tiers'), $location_tier, $location_id);

			$selCategories = $criteria['show_cadres'] ? ',GROUP_CONCAT(distinct ec.qualification_phrase) as cadres ' : "";


			$sql = "SELECT
					partner.*,
					partner.id,partner.partner,partner.location_id,".implode(',',$locationFlds)."
					,GROUP_CONCAT(distinct facility.facility_name) as facilities
					,CASE WHEN annual_cost REGEXP '[^!0-9,\.][0-9\.,]+' THEN SUBSTRING(annual_cost, 2) ELSE annual_cost END AS 'annual_cost_to_compare'
					,COUNT(distinct e.id) AS pcnt
					$selCategories
					FROM partner LEFT JOIN ($locationsubquery) as l ON l.id = partner.location_id
					LEFT JOIN partner_to_subpartner_to_funder_to_mechanism funders ON partner.id = funders.partner_id
					LEFT JOIN partner_funder_option funderopt ON funders.partner_funder_option_id = funderopt.id
					-- LEFT JOIN partner_to_subpartner subpartners ON subpartners.partner_id = partner.id
					LEFT JOIN employee e on e.partner_id = partner.id
					LEFT JOIN facility ON e.site_id = facility.id";
#todo is_deleted not implemented
			if ($criteria['facility_type_option_id']) $sql .= " LEFT JOIN facility_type_option fto ON fto.id = facility.type_option_id ";
			if ($criteria['show_cadres'])             $sql .= " LEFT JOIN employee_qualification_option ec ON ec.id = e.employee_qualification_option_id ";

			// restricted access?? only show partners by organizers that we have the ACL to view
			$org_allowed_ids = allowed_org_access_full_list($this); // doesnt have acl 'training_organizer_option_all'
			if ($org_allowed_ids)                             $where[] = "partner.organizer_option_id in ($org_allowed_ids)";
			// restricted access?? only show organizers that belong to this site if its a multi org site
			$site_orgs = allowed_organizer_in_this_site($this); // for sites to host multiple training organizers on one domain
			if ($site_orgs)                                   $where[] = "partner.organizer_option_id in ($site_orgs)";

			// criteria
			if ($criteria['partner_id'])                      $where[] = 'partner.id = '.$criteria['partner_id'];

			if ($criteria['last_selected_rgn'])               $where[] = 'province_name is not null'; // bugfix - location subquery is not working like a inner join or where, not sure why

			if ($criteria['facilityInput'])                   $where[] = 'facility.id = '.$criteria['facilityInput'];

			if ($criteria['facility_type_option_id'])         $where[] = 'facility.type_option_id = '.$criteria['facility_type_option_id'];

			if ($criteria['employee_qualification_option_id'])$where[] = 'employee_qualification_option_id = '.$criteria['employee_qualification_option_id'];

			if ($criteria['employee_category_option_id'])     $where[] = 'employee_category_option_id = '.$criteria['employee_category_option_id'];

			if ($criteria['hours_min'])                       $where[] = 'e.funded_hours_per_week >=' .$criteria['hours_min'];
			if ($criteria['hours_max'])                       $where[] = 'e.funded_hours_per_week <=' .$criteria['hours_min'];

			if ($criteria['cost_min'])                        $where[] = 'e.annual_cost_to_compare >=' .$criteria['cost_min'];
			if ($criteria['cost_max'])                        $where[] = 'e.annual_cost_to_compare <=' .$criteria['cost_max'];

			#TODO: marking EMPLOYEE ROLE, TRANSITION CONFIRMED, START_DATE, END_DATE as TO BE REMOVED at clients request. these are disabled in the view
			if ($criteria['employee_role_option_id'])         $where[] = 'funding_end_date >= \''.$this->_date_to_sql( $criteria['start_date'] ) .' 00:00:00\'';

			if ($criteria['partner_importance_option_id'])    $where[] = 'partner_importance_option_id = ' .$criteria['partner_importance_option_id'];

			if ($criteria['start_date'])                      $where[] = 'funding_end_date >= \''.$this->_date_to_sql( $criteria['start_date'] ) .' 00:00:00\'';

			if ($criteria['end_date'])                        $where[] = 'funding_end_date <= \''.$this->_date_to_sql( $criteria['end_date'] ) .' 23:59:59\'';

			if ($criteria['employee_transition_option_id'])   $where[] = 'employee_transition_option_id = ' .$criteria['employee_transition_option_id'];

			if ($criteria['transition_confirmed'])            $where[] = 'transition_confirmed = 1';

			if ( count ($where) )
				$sql .= ' WHERE ' . implode(' AND ', $where);

			$sql .= ' GROUP BY partner.id ';

			$db = $this->dbfunc();
			$rowArray = $db->fetchAll( $sql );
			$this->viewAssignEscaped ('results', $rowArray );
			$this->view->assign ('count', count($rowArray) );

			if ($criteria ['outputType']) {
				$this->sendData ( $this->reportHeaders ( false, $rowArray ) );
			}
		}


		// assign form drop downs
		$this->view->assign ( 'status',   $status );
		$this->view->assign ( 'criteria', $criteria );
		$this->view->assign ( 'pageTitle', t('Reports'));
		$this->viewAssignEscaped ( 'locations', Location::getAll () );
		$this->view->assign ( 'partners',    DropDown::generateHtml ( 'partner', 'partner', $criteria['partner_id'], false, $this->view->viewonly, false ) ); //table, col, selected_value
		$this->view->assign ( 'subpartners', DropDown::generateHtml ( 'partner', 'partner', $criteria['subpartner_id'], false, $this->view->viewonly, false, true, array('name' => 'subpartner_id'), true ) );
		$this->view->assign ( 'importance',  DropDown::generateHtml ( 'partner_importance_option', 'importance_phrase', $criteria['partner_importance_option_id'], false, $this->view->viewonly, false ) );
		$this->view->assign ( 'transitions', DropDown::generateHtml ( 'employee_transition_option', 'transition_phrase', $criteria['employee_transition_option_id'], false, $this->view->viewonly, false ) );
		$this->view->assign ( 'incomingPartners', DropDown::generateHtml ( 'partner', 'partner', $criteria['incoming_partner'], false, $this->view->viewonly, false, true, array('name' => 'incoming_partner'), true ) );
		$helper = new Helper();
		$this->viewAssignEscaped ( 'facilities', $helper->getFacilities() );
		$this->view->assign ( 'facilitytypes', DropDown::generateHtml ( 'facility_type_option', 'facility_type_phrase', $criteria['facility_type_option_id'], false, $this->view->viewonly, false ) );
		$this->view->assign ( 'cadres',        DropDown::generateHtml ( 'employee_qualification_option', 'qualification_phrase', $criteria['employee_qualification_option_id'], false, $this->view->viewonly, false ) );
		$this->view->assign ( 'categories',    DropDown::generateHtml ( 'employee_category_option', 'category_phrase', $criteria['employee_category_option_id'], false, $this->view->viewonly, true ) );
		$this->view->assign ( 'roles',         DropDown::generateHtml ( 'employee_role_option', 'role_phrase', $criteria['employee_role_option_id'], false, $this->view->viewonly, false ) );
		$this->view->assign ( 'transitions',   DropDown::generateHtml ( 'employee_transition_option', 'transition_phrase', $criteria['employee_transition_option_id'], false, $this->view->viewonly, false ) );
	}


	public function mechanismsAction() {
		require_once ('models/table/Helper.php');
		require_once ('views/helpers/FormHelper.php');
		require_once ('views/helpers/DropDown.php');
		require_once ('views/helpers/Location.php');
		require_once ('views/helpers/CheckBoxes.php');
		require_once ('views/helpers/TrainingViewHelper.php');

		$criteria = $this->getAllParams();
		$db = $this->dbfunc();

		if ($criteria['go'])
		{

			$where = array();

			// TODO: This special case report is bad
			if($criteria['report'] == "subpartnerEmployees") {
				$sql = "SELECT partner.partner, link_mechanism_partner.partner_id
						FROM link_mechanism_partner
						INNER JOIN partner ON link_mechanism_partner.partner_id = partner.id
						WHERE link_mechanism_partner.mechanism_option_id = {$criteria['mechanism_id']}";
				$sql = "SELECT
partner.partner,
link_mechanism_partner.partner_id,
mechanism_option.mechanism_phrase
FROM
link_mechanism_partner
INNER JOIN partner ON link_mechanism_partner.partner_id = partner.id
INNER JOIN mechanism_option ON link_mechanism_partner.mechanism_option_id = mechanism_option.id
WHERE link_mechanism_partner.mechanism_option_id = {$criteria['mechanism_id']}";
				$rowArray = $db->fetchAll($sql);
				foreach($rowArray as &$row) {
				    //TA:#464
// 					$sql = "SELECT COUNT(*) as numberEmployees
// 							FROM employee
// 							INNER JOIN link_mechanism_employee ON link_mechanism_employee.employee_id = employee.id
// 							WHERE employee.partner_id = {$row['partner_id']} AND
// 							link_mechanism_employee.mechanism_option_id = {$criteria['mechanism_id']}";
				    $sql = "SELECT COUNT(*) as numberEmployees
				    FROM employee
				    INNER JOIN link_employee_facility ON link_employee_facility.employee_id = employee.id
				    WHERE employee.partner_id = {$row['partner_id']} AND
				    link_employee_facility.mechanism_option_id = {$criteria['mechanism_id']}";
					$row['numberEmployees'] = $db->fetchOne($sql);
				}
				$this->viewAssignEscaped('results', $rowArray);
				$this->view->assign('count', count($rowArray));

				if ($criteria ['outputType']) {
					$this->sendData($this->reportHeaders(false, $rowArray));
				}

			}

			switch ($criteria['report']) {
				case "defined":

					$sql = "
							select sfm.id, subp.partner as subpartner, funder_phrase, mechanism_phrase, sfm.funding_end_date
							from subpartner_to_funder_to_mechanism sfm
							left join partner subp on subp.id = sfm.subpartner_id
							left join partner_funder_option pf on pf.id = sfm.partner_funder_option_id
							left join mechanism_option m on m.id = sfm.mechanism_option_id
							";
					break;

				case "definedByPartner":

					$sql = "
							select psfm.id, p.partner, subp.partner as subpartner, funder_phrase, mechanism_phrase, psfm.funding_end_date
							from partner_to_subpartner_to_funder_to_mechanism psfm
							left join partner p on p.id = psfm.partner_id
							left join partner subp on subp.id = psfm.subpartner_id
							left join partner_funder_option pf on pf.id = psfm.partner_funder_option_id
							left join mechanism_option m on m.id = psfm.mechanism_option_id
							";
					break;

				case "definedByEmployee":

					$sql = "
							select epsfm.id, e.employee_code, p.partner, subp.partner as subpartner, funder_phrase, mechanism_phrase, epsfm.percentage
							from employee_to_partner_to_subpartner_to_funder_to_mechanism epsfm
							left join employee e on e.id = epsfm.employee_id
							left join partner p on p.id = epsfm.partner_id
							left join partner subp on subp.id = epsfm.subpartner_id
							left join partner_funder_option pf on pf.id = epsfm.partner_funder_option_id
							left join mechanism_option m on m.id = epsfm.mechanism_option_id
							";
					break;
			}

			// criteria
			if ($criteria['partner_id'] && $criteria['report'] != 'defined' ) {
				$where[] = 'p.id = '.$criteria['partner_id'];
			}

			if ($criteria['subpartner_id']) {
				$where[] = 'subp.id = '.$criteria['subpartner_id'];
			}

			if ($criteria['start_date']) {
				$where[] = 'funding_end_date >= \'' . $this->_date_to_sql($criteria['start_date']) . ' 00:00:00\'';
			}
			if ($criteria['end_date']) {
				$where[] = 'funding_end_date <= \'' . $this->_date_to_sql($criteria['end_date']) . ' 23:59:59\'';
			}

			switch ($criteria['report']) {

				case "defined":
					if ( count ($where) ){
						$sql .= ' WHERE ' . implode(' AND ', $where);
						$sql .= ' AND sfm.is_deleted = false ';
					}
					else  {
						$sql .= ' WHERE sfm.is_deleted = false ';
					}
					$sql .= ' order by subp.partner, funder_phrase, mechanism_phrase ';
					break;
				case "definedByPartner":
					if ( count ($where) ){
						$sql .= ' WHERE ' . implode(' AND ', $where);
						$sql .= ' AND psfm.is_deleted = false ';
					}
					else {
						$sql .= ' WHERE psfm.is_deleted = false ';
					}
					$sql .= ' order by p.partner, subp.partner, funder_phrase, mechanism_phrase ';
					break;
				case "definedByEmployee":
					if ( count ($where) ){
						$sql .= ' WHERE ' . implode(' AND ', $where);
						$sql .= ' AND epsfm.is_deleted = false ';
					}
					else {
						$sql .= ' WHERE epsfm.is_deleted = false ';
					}
					$sql .= ' order by e.employee_code, p.partner, subp.partner, funder_phrase, mechanism_phrase ';
					break;
			}
			// TODO: This special case is bad.
			if($criteria['report'] != "subpartnerEmployees") {
				$rowArray = $db->fetchAll($sql);
				$this->viewAssignEscaped('results', $rowArray);
				$this->view->assign('count', count($rowArray));

				if ($criteria ['outputType']) {
					$this->sendData($this->reportHeaders(false, $rowArray));
				}
			}
		}

		$sql = "SELECT id, mechanism_phrase from mechanism_option where is_deleted = 0 order by mechanism_phrase ASC";
		$mechanisms = $db->fetchAll($sql);

		// assign form drop downs
		$this->view->assign('criteria', $criteria);
		$this->view->assign('pageTitle', t('Reports'));
		$this->view->assign('report', $criteria['report']);
		$this->view->assign('mechanisms', $mechanisms);

		$this->view->assign ('partners',    DropDown::generateHtml ( 'partner', 'partner', $criteria['partner_id'], false, $this->view->viewonly, false)); //table, col, selected_value
		$this->view->assign ('subpartners', DropDown::generateHtml ( 'partner', 'partner', $criteria['subpartner_id'], false, $this->view->viewonly, false, true, array('name' => 'subpartner_id'), true));
	}
}
