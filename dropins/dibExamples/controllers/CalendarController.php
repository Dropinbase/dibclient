<?php

class CalendarController extends Controller {
	
	public function read ($containerName, $start=null, $end=null, $cid=null, $submissionData=null, $activeFilter=null) {
		$criteria = ''; 
		if($activeFilter) {

			// Get filter 
			$sql = "SELECT i.filter, i.filter_syntax, ^^CONCAT(c2.name, '_', i.name)^^ as rrr
					FROM pef_item i INNER JOIN pef_container c on i.pef_child_container_id = c.id
					INNER JOIN pef_container c2 ON i.pef_container_id = c2.id
			        WHERE c.name = :name AND ^^CONCAT(c2.name, '_', i.name)^^ =:activeFilter";
			$rst = Database::fetch($sql, array(':name'=>$containerName, ':activeFilter'=>$activeFilter));

			if ($rst === FALSE || Database::count()===0)
				return $this->invalidResult('Invalid filter configuration. Please contact the System Administrator.');
			
			$filter = $rst['filter'];
			$filterSyntax = $rst['filter_syntax'];
			$criteria = " AND ($filter)";
			$filterParams = EvalCriteria::getParams($filter, FALSE);
			$submissionData = $this->getSubmitValues($submissionData);
			foreach ($filterParams as $param) {
				if(isset($submissionData[$param]))
					$params[':'.$param] = $submissionData[$param];
			}
		}
		
		$params[':fromDate'] = $start;
		$params[':toDate'] = $end;
		
		$sql = "SELECT id as EventId, dib_calendar_id as CalendarId, `title` as `Title`, from_date as `StartDate`, 
		        to_date as `EndDate`, `location` as `Location`, `url` as `Url`, reminder_min as `Reminder`, 
		        allday as `IsAllDay`, notes as `Notes`
		        FROM dib_calendar_item
		        WHERE from_date BETWEEN :fromDate AND :toDate $criteria";
		
		$rst = Database::execute($sql, $params);
		
		if ($rst === FALSE)
			return $this->invalidResult('Could not read calendar events. Please contact the System Administrator.');

		return $this->validResult($rst);
		
	}
	
	public function create ($containerName) { // *** containerName NEEDED!!
		$post = PeffApp::jsonDecode($this->readInput(), FALSE);
		$record = array('dib_calendar_id'=>$post['CalendarId'], 'title'=>$post['Title'], 'from_date'=>$post['StartDate'],
		        'to_date'=>$post['EndDate'], 'location'=>$post['Location'], 'url'=>$post['Url'], 
		        'reminder_min'=> (($post['Reminder']) ? $post['Reminder'] : null),
		        'allday'=>$post['IsAllDay'], 'notes'=>$post['Notes']);
		        
		$result = Crud::create('dib_calendar_item_grid', $record);
		
		if(isset($result[0]) && $result[0]==='error')
			return $this->invalidResult('Could not create new event. Please contact the System Administrator.');
	   
	    $post['EventId']=$result['id'];
		return $this->validResult($post);
	}
	
	public function update ($containerName) {
		$post = PeffApp::jsonDecode($this->readInput(), FALSE);
		$record = array('id'=>$post['EventId'], 'dib_calendar_id'=>$post['CalendarId'], 'title'=>$post['Title'], 'from_date'=>$post['StartDate'],
		        'to_date'=>$post['EndDate'], 'location'=>$post['Location'], 'url'=>$post['Url'], 'reminder_min'=>$post['Reminder'],
		        'allday'=>$post['IsAllDay'], 'notes'=>$post['Notes']);
		        
		$result = Crud::update('dib_calendar_item_grid', array('id'=>$post['EventId']), $record);
		
		if($result !== true)
			return $this->invalidResult('Could not update event. Please contact the System Administrator.');
		
		return $this->validResult($post);
	}
	
	public function delete ($containerName, $id=null) {
		$post = PeffApp::jsonDecode($this->readInput(), FALSE);
		
		$result = Crud::delete('dib_calendar_item_grid', array('id'=>$post['EventId']));
		
		if($result !== true)
			return $this->invalidResult('Could not delete event. Please contact the System Administrator.');
		return $this->validResult(NULL);
	}
	
	// green: 49D32C  blue: 123EED 
	public function calendarNames ($containerName) {
		$sql = "SELECT id as CalendarId, `title` as `Title`, description as `Description`, 
		        colorId as `ColorId`, `is_hidden` as `IsHidden`
		        FROM dib_calendar";
		
		$rst = Database::execute($sql);
		
		if ($rst === FALSE || Database::count() === 0)
			return $this->invalidResult('Could not read calendar names. Please contact the System Administrator.');

		return $this->validResult($rst);
	}
	
}
?>