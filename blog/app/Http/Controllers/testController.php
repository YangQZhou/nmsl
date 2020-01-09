<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

//编码设置
header('Content -type:text/html;charset=utf-8');

class testController extends Controller
{

    //
    public function getFiles(Request $request){
        //得到数据
        $studentName = $request->get('studentName');
        $studentNo = $request->get('studentNo');
        $actionName = $request->get('actionName');
        $School = $request->get('School');

        //上传文件不存在错误
        if($_FILES["file"]["error"] <1){
            $Folder="videos/";
            //以学校/学号+姓名命名一个文件夹，用来存视频
            $pre_student_folder=$Folder.$School."/".$studentNo.$studentName."/";
            //不存在就新建
            if(!file_exists("$pre_student_folder")){
                //构建存放视频的文件夹
                mkdir($pre_student_folder,0777,true);
                //再新建一个combine文件夹
                mkdir($pre_student_folder."combine/",0777,true);
            }
            //用学生的姓名+动作命名一个视频
            $videoName = $studentName.$actionName.'.'.explode('/', $_FILES["file"]["type"])[1];
            $videoPath = $pre_student_folder.$videoName;

            //存在原文件就删除原本的视频
            if(file_exists($videoPath))
            {
                unlink($videoPath);
            }
            //保存文件
            $result = move_uploaded_file($_FILES["file"]["tmp_name"],$videoPath);
            if($result) {
                $changePath = $pre_student_folder.$studentName.$actionName.".mp4";
                //保存成功开始转码
                if($this->changeTypeToMP4($videoPath,$changePath)) {
                    return "上传成功";
                }else{
                    return "转码失败，请联系开发后台的那个傻逼";
                }

            }
            else {
                return "上传失败，请检查网络，多次失败请联系开发者";
            }
        }
        else {
            return "错误编号:".$_FILES["file"]["error"]."，请联系开发者";
        }
    }

    public function changeTypeToMP4($videoPath,$changePath)
    {
        if(file_exists($changePath)) {
            unlink($changePath);
        }
        if(file_exists($videoPath)) {
            $str = "ffmpeg -i ".$videoPath." -y ".$changePath;
            exec($str);
            if(file_exists($changePath)) {
                unlink($videoPath);
                return true;
            }
        }
        return false;
    }

    public function getCombineFiles(Request $request)
    {
        //得到数据
        $studentName = $request->get('studentName');
        $studentNo = $request->get('studentNo');
        //$actionName = $request->get('actionName');
        $School = $request->get('School');
        $videoNO = $request->get('videoNO');

        if($_FILES["file"]["error"] <1){
            $Folder="videos/";
            //以学校/学号+姓名命名一个文件夹，用来存视频
            $pre_student_folder=$Folder.$School."/".$studentNo.$studentName."/";
            //不存在就新建
            if(!file_exists("$pre_student_folder")){
                //构建存放视频的文件夹
                mkdir($pre_student_folder,0777,true);
                //再新建一个combine文件夹
                mkdir($pre_student_folder."combine/",0777,true);
            }
            //保存需要合并的视频
            $videoName = $videoNO.'.'.explode('/', $_FILES["file"]["type"])[1];
            $videoPath = $pre_student_folder."combine/".$videoName;

            //存在原文件就删除原本的视频
            if(file_exists($videoPath)) {
                unlink($videoPath);
            }
            //保存文件
            $result = move_uploaded_file($_FILES["file"]["tmp_name"],$videoPath);
            if($result) {
                $changePath = $pre_student_folder."combine/".$videoNO.".mp4";
                $changePath2 = $pre_student_folder."combine/".$videoNO.".ts";
                //保存成功开始转码
                if($this->changeTypeToMP4($videoPath,$changePath) && $this->changeTypeToTS($changePath,$changePath2)) {
                    //开始判断是否要合并
                    $combineFolder = $pre_student_folder."combine/";
                    if($this->combine($combineFolder))
                    {
                        return "合并成功";
                    }
                    else{
                        return "上传成功，未须合并";
                    }
                }else{
                    return "转码失败，请联系开发后台的那个傻逼";
                }
            }
            else {
                return "上传失败，请检查网络，多次失败请联系开发者";
            }
        }
        return "错误编号:".$_FILES["file"]["error"]."，请联系开发者";
    }

    public function changeTypeToTS($changePath,$changePath2){
        //删除原本视频
        if(file_exists($changePath2)) {
            unlink($changePath2);
        }
        $str = "ffmpeg -i ".$changePath." -vcodec copy -acodec copy -vbsf h264_mp4toannexb ".$changePath2;
        exec($str);
        //删除原本视频
        if(file_exists($changePath2)) {
            unlink($changePath);
            return true;
        }
        return false;
    }

    public function combine($combineFolder)
    {
        //如果合并之前有存在视频
        if(file_exists($combineFolder."考试中.mp4")) {
            unlink($combineFolder."考试中.mp4");
        }
        $videos_arr = array();
        $files = scandir($combineFolder);
        foreach ($files as $pre_video_name) {
            if($pre_video_name != '.' && $pre_video_name != '..'){
                $videos_arr[] = $pre_video_name;
            }
        }
        if(in_array("1.ts",$videos_arr) &&
            in_array("2.ts",$videos_arr) &&
            in_array("3.ts",$videos_arr) &&
            in_array("4.ts",$videos_arr) &&
            in_array("5.ts",$videos_arr) &&
            in_array("6.ts",$videos_arr) &&
            in_array("7.ts",$videos_arr) &&
            in_array("8.ts",$videos_arr) &&
            in_array("9.ts",$videos_arr) &&
            in_array("10.ts",$videos_arr))
        {
            $str2 = "ffmpeg -i \"concat:".
                $combineFolder."1.ts|".$combineFolder."2.ts|".
                $combineFolder."3.ts|".$combineFolder."4.ts|".
                $combineFolder."5.ts|".$combineFolder."6.ts|".
                $combineFolder."7.ts|".$combineFolder."8.ts|".
                $combineFolder."9.ts|".$combineFolder."10.ts\" -acodec copy -vcodec copy -absf aac_adtstoasc ".$combineFolder."考试中.mp4";
            //执行合并
            exec($str2);

            if(file_exists($combineFolder."考试中.mp4")) {
                //删除ts文件
                for($i=1;$i<=10;$i++){
                    $deletePath = $combineFolder.$i.".ts";
                    IF(file_exists($deletePath)){
                        unlink($deletePath);
                    }
                }
                return true;
            }
        }
        return false;
    }

}
