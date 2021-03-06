<?php

namespace Topxia\Service\Course\Dao;

interface LessonLearnDao
{
	public function getLearn($id);

	public function getLearnByUserIdAndLessonId($userId, $lessonId);

	public function findLearnsByUserIdAndCourseId($userId, $courseId);

	public function findLearnsByUserIdAndCourseIdAndStatus($userId, $courseId, $status);

	public function getLearnCountByUserIdAndCourseIdAndStatus($userId, $courseId, $status);

    public function findLearnsByLessonId($lessonId, $start, $limit);

    public function findLearnsCountByLessonId($lessonId);

	public function addLearn($learn);

	public function updateLearn($id, $fields);

    public function deleteLearnsByLessonId($lessonId);
}