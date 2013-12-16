<?php

namespace Topxia\DataTag;

use Topxia\DataTag\DataTag;

class ElitedCourseQuestionsDataTag extends BaseDataTag implements DataTag  
{
    /**
     * 获取精选课程问答列表
     *
     * 可传入的参数：
     *   courseId 必需 课程ID
     *   count 必需 课程话题数量，取值不能超过100
     * 
     * @param  array $arguments 参数
     * @return array 课程话题
     */

    public function getData(array $arguments)
    {
        if (empty($arguments['courseId'])) {
            throw new \InvalidArgumentException("courseId参数缺失");
        }
        if (empty($arguments['count'])) {
            throw new \InvalidArgumentException("count参数缺失");
        }
        if ($arguments['count'] > 100) {
            throw new \InvalidArgumentException("count参数超出最大取值范围");
        }
        $conditions = array( 'courseId' => $arguments['courseId'],'type' => 'question','isElite' => '1');
    	return $this->getThreadService()->searchThreads($conditions, 'created', 0, $arguments['count']);
    }

    protected function getThreadService()
    {
        return $this->getServiceKernel()->createService('Course.ThreadService');
    }

}



?>