<?php

use App\Clazz;
use App\ClazzStream;

trait ReportHelper
{

    static function genResults($clazz_id, $by, $term){


        $all_data = [];
        $genResultsFor = ($by == "clazz") ? Clazz::find($clazz_id) : ClazzStream::find($clazz_id);
        $clazz = ($by == "clazz") ? Clazz::find($clazz_id) : ClazzStream::find($clazz_id)->clazz;
        $reportConfig = ($by == "clazz") ? $genResultsFor->reportConfig : $genResultsFor->clazz->reportConfig;
        $subjects = ($by == "clazz") ? $genResultsFor->subjects : $genResultsFor->clazz->subjects;
        $students = $genResultsFor->students;
        $exam_sets = json_decode($reportConfig->exam_sets);


        foreach($students as $student){

            $resulst_data = [];
            $student_all_average = [];
            foreach($subjects as $subject){
               // $subject_data = [];
                if($subject->particulars->count() > 0) {
                    foreach($subject->particulars as $particular){
                        if(in_array($particular->id, $clazz->patsForSubject($subject->id)->pluck("subject_pat_id")->toArray())){
                            $mark = self::MarkByExamSet($exam_sets, $clazz, $student, $term, $subject, $reportConfig->grading_id);
                        }
                    }
                }else{
                    $mark = self::MarkByExamSet($exam_sets, $clazz ,$student, $term, $subject, $reportConfig->grading_id);
                }
                $subject_data= [
                    "result" => $mark->all_data,
                    "final_result" => $mark->final_results
                ];
                $student_all_average["total"][] = $mark->final_results["all_average"]["total"];
                $student_all_average["points"][] = $mark->final_results["all_average"]["points"];
                $resulst_data[$subject->name] = $subject_data;
            }

            $data = array(
                "student_name" => $student->name,
                "student_id" => $student->id,
                "results" => $resulst_data,
                "all_avg" => [
                    "total" => collect($student_all_average['total'])->sum(),
                    "points" => collect($student_all_average['points'])->sum(),
                ] 
            );

            $all_data[] = $data;
        }

        return (object)["student_results" => $all_data, "report_config" => $reportConfig];

    }

    static function MarkByExamSet($exam_sets, $clazz, $student, $term, $subject, $grade_id){
    
        $total_mark = 0;
        $sets_count = count($exam_sets);
        
        foreach($exam_sets as $exam_set){
            //$data = [];
            $other_data = [];
            $total_marks = 0;
            $_marks = [];

            $exam = \App\ExamSet::find($exam_set);
            

            if ($subject->particulars->count() > 0) {
                $_all_marks = [];
                $total_marks = 0;
                foreach ($subject->particulars as $particular) {
                   
                    $total_per_subject = 0;
                    if (in_array($particular->id, $clazz->patsForSubject($subject->id)->pluck("subject_pat_id")->toArray())) {
                        $markData = self::markData($student, $subject, $exam, $term, $particular->id);
                        $total_marks += $markData->grade_by;
                        $grade_data = self::getMarkGrade($grade_id, $markData->grade_by);
                        $_marks[] = array(
                            "mark" => $markData->the_mark,
                            "g_symbol" => $grade_data->symbol,
                            "points" => $grade_data->consist_of,
                            "particular" => $particular->name
                        );

                        //$_all_marks[] = $_marks;
                        $data[$exam->short_name] = $_marks;
                        
                    }
                }
               // $data[$exam->short_name] = $_all_marks;               
                
            } else {
                $markData = self::markData($student, $subject,$exam, $term, null);
                $total_marks += $markData->grade_by;
                $grade_data = self::getMarkGrade($grade_id, $markData->grade_by);
                $data[$exam->short_name][] = array(
                    "mark" => $markData->the_mark,
                    "g_symbol" => $grade_data->symbol,
                    "points" => $grade_data->consist_of,
                    "particular" => ''
                );

            
            }

            //$all_data[] = $data;
            //array_push($all_data, $data);
          
           
        }

        $res = collect($data)->flatten(1)->toArray();
        $count  = count($res);


        //dd($res);

       // dd($all_data);

        $all_data_collect = [];
        foreach($res as $key => $val){
            if((count($val) != count($val, 1))){
                foreach($val as $k => $v){
                   // $all_data_collect[$k][$v[0]["particular"]][] = $v[0]["mark"];
                  // dd($v);
                    $all_data_collect[$v["particular"]][] = $v["mark"];
                }
               // dump($v);
            }else{
                $all_data_collect[$val["particular"]][] = $val["mark"];
            }
        }

    
        $final_res_data = [];
        foreach($all_data_collect as $key => $val){
            $collect_val = collect($val); 
            $total = $collect_val->flatten(1)->sum();
            $avg_mark = round($total/ $collect_val->flatten(1)->count());
            $grade_mark = self::getMarkGrade($grade_id, $avg_mark);    
            $final_res_data[] = array(
                "total" => $total,
                "avg_mark" => $avg_mark,
                "points" => $grade_mark->consist_of,
                "symbol" => $grade_mark->symbol
            ); 
        }

        

        $final_res_data_sum = collect($final_res_data);
        $total = round($final_res_data_sum->pluck("avg_mark")->sum() / $final_res_data_sum->count());
        $final_grade_mark = self::getMarkGrade($grade_id, $total);
        $final_res_data["all_average"] = array(
            "total" => $total,
            "points" => $final_grade_mark->consist_of,
            "symbol" => $final_grade_mark->symbol,
            "comment" => $final_grade_mark->comment
        );
        return (object)[ "all_data" => $data, "final_results" => $final_res_data];
    }

    static function markData($student, $subject ,$exam, $term, $pat_id){
        $mark = $student->mark($student->id, $exam->id, $term, $subject->id, $pat_id);

        if ($mark != null) {
            $the_mark = $mark->mark;
            $grade_by = $the_mark;
        } else {
            $the_mark = '';
            $grade_by = 0;
        }

        return (object)[
            "the_mark" => $the_mark,
            "grade_by" => $grade_by
        ];
    }

    static function getMarkGrade($grade_id, $mark = 0){
        $data = null;
        $grade = \App\Grading::find($grade_id);
        foreach($grade->details as $grade_d){
            if(\Util::in_range($mark, $grade_d->mark_end, $grade_d->mark_start)){
                $data = $grade_d;
                break;
            }
        }

        return $data;

    }

    static function determingPosition($clazz_id, $by , $term){

        //by can be class or stream id
        $data = ReportHelper::genResults($clazz_id, $by, $term);

        $score_by = $data->report_config->score_by;
        $position_by = $data->report_config->position_by;
        $points_by = $data->report_config->points_by;
        $grading_by = $data->report_config->advanced_config;

        $clazz = ($by == "clazz") ? Clazz::find($clazz_id) : ClazzStream::find($clazz_id)->clazz;

        $collect_data = collect($data->student_results);
        $type = "points";

        if($position_by == "marks"){
            $con = $collect_data->sortByDesc(function ($data, $key) {
                return $data["all_avg"]["total"];
            });
        }else{

            if($points_by == "asc"){
                $con = $collect_data->sortByDesc(function ($data, $key) {
                    return $data["all_avg"]["total"];
                 })->sortBy(function ($data, $key) use ($type) {
                     return $data["all_avg"]["points"];
                  });
            }else{
                $con = $collect_data->sortByDesc(function ($data, $key) {
                    return $data["all_avg"]["total"];
                })->sortByDesc(function ($data, $key) use ($type) {
                    return $data["all_avg"]["points"];
                });
            }
            
        }
        


        $position = 1;
        foreach ($con as $value) {
            $new_value = collect($value)->put("position", $position++ . "/" . $con->count());
            \App\StudentReport::create([
                "student_id" => $new_value["student_id"],
                "clazz_id" => $clazz_id,
                "term_id" => $term,
                "full_report" => json_encode($new_value)
            ]);
    
        }

    }
    
}