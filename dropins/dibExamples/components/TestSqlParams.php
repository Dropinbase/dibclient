<?php

class TestSqlParams {

    public static function testCrit(&$params, $submissionData) {
        $params[':mine'] = 5;
        return 't.id > :mine';
        
    }

}

