<?php
namespace Topxia\WebBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Topxia\Common\ArrayToolkit;
use Topxia\Common\Paginator;

class QuizQuestionTestController extends BaseController
{
	public function createAction(Request $request, $courseId)
	{
		$course = $this->getCourseService()->tryManageCourse($courseId);

		$testPaper = $request->query->all();

	    if ($request->getMethod() == 'POST') {
	    	$testPaper = $request->request->all();

        	if(empty($testPaper['testPaperId'])){
	        	$result = $this->getTestService()->createTestPaper($testPaper);
        	}else{
	        	$result = $this->getTestService()->updateTestPaper($testPaper['testPaperId'], $testPaper);
        	}

	        $testPaper['courseId'] = $courseId;
	        $testPaper['testPaperId'] = $result['id'];
			return $this->redirect($this->generateUrl('course_manage_test_item',$testPaper));
        }

        if(empty($testPaper['target'])){
			throw $this->createNotFoundException('target 参数不对');
		}

        if(!empty($testPaper['testPaperId'])){
			$paper = $this->getTestService()->getTestPaper($testPaper['testPaperId']);
			$testPaper = array_merge($testPaper, $paper);
		}


		return $this->render('TopxiaWebBundle:QuizQuestionTest:create.html.twig', array(
			'course'    => $course,
			'testPaper' => $testPaper,
		));
	}

	public function indexItemAction(Request $request, $courseId, $testPaperId)
	{
        $parentTestPaper = $request->query->all();
        if(!empty($parentTestPaper)){
        	$field['itemCounts']  = $parentTestPaper['itemCounts'];
			$field['itemScores']  = $parentTestPaper['itemScores'];
        }
        $typeNumer = $this->getQuestionService()->findQuestionsForTestPaper($field, $courseId);
        header('Content-type:text/html;charset=utf-8');
        echo "<pre>";var_dump($typeNumer);echo "</pre>"; exit();
		$course    = $this->getCourseService()->tryManageCourse($courseId);
		$lessons   = ArrayToolkit::index($this->getCourseService()->getCourseLessons($courseId),'id');

		$testPaper = $this->getTestService()->getTestPaper($testPaperId);
		$items     = $this->getTestService()->findItemsByTestPaperId($testPaperId);
		$questions = ArrayToolkit::index($this->getQuestionService()->findQuestionsByIds(ArrayToolkit::column($items, 'questionId')), 'id');
		return $this->render('TopxiaWebBundle:QuizQuestionTest:index.html.twig', array(
			'course' => $course,
			'items' => $items,
			'questions' => $questions,
			'testPaper' => $testPaper,
			'lessons' => $lessons,
			'parentTestPaper' => $parentTestPaper
		));
	}

	public function itemListAction(Request $request, $courseId,  $testPaperId)
	{
		$replaceId = $request->query->get('testItemId');

		$type = $request->query->get('type');
        if(empty($type)){
            throw $this->createNotFoundException('type 参数不对');
        }
		$type = explode('-', $type);

		$course    = $this->getCourseService()->tryManageCourse($courseId);
		$lessons   = $this->getCourseService()->getCourseLessons($courseId);
		$testPaper = $this->getTestService()->getTestPaper($testPaperId);
		$itemIds   = ArrayToolkit::column($this->getTestService()->findItemsByTestPaperIdAndQuestionType($testPaperId, $type), 'questionId');

        $conditions['target']['course'] = array($courseId);
        if (!empty($lessons)){
            $conditions['target']['lesson'] = ArrayToolkit::column($lessons,'id');;
        }
        $conditions['parentId'] = 0;
        $conditions[$type['0']] = $type['1'];
        $conditions['notId']    = $itemIds;

        $paginator = new Paginator(
			$this->get('request'),
			$this->getQuestionService()->searchQuestionCount($conditions),
			5
		);

        $questions = $this->getQuestionService()->searchQuestion(
        		$conditions, 
        		array('createdTime' ,'DESC'), 
        		$paginator->getOffsetCount(),
                $paginator->getPerPageCount()
        );

		$lessons = ArrayToolkit::index($lessons,'id');
		$users = $this->getUserService()->findUsersByIds(ArrayToolkit::column($questions, 'userId')); 

		return $this->render('TopxiaWebBundle:QuizQuestionTest:create-list.html.twig', array(
			'course' => $course,
			'lessons' => $lessons,
			'questions' => $questions,
			'testPaper' => $testPaper,
			'parentId' => false,
			'users' => $users,
			'paginator' => $paginator,
			'replaceId' => $replaceId
		));
	}

	public function createItemAction(Request $request, $courseId,  $testPaperId)
	{
		$questionId = $request->query->get('questionId');
		$replaceId  = $request->query->get('replaceId');

		$course    = $this->getCourseService()->tryManageCourse($courseId);

		$lessons   = ArrayToolkit::index($this->getCourseService()->getCourseLessons($courseId), 'id');

		$question = $this->getQuestionService()->getQuestion($questionId);

		$questions[$question['id']] = $question; 

		$testPaper = $this->getTestService()->getTestPaper($testPaperId);

		if (!empty($replaceId)){
			$item = $this->getTestService()->updateItem($replaceId, $questionId);
		} else {
			$item = $this->getTestService()->createItem($testPaperId, $questionId);
		}
        
		return $this->render('TopxiaWebBundle:QuizQuestionTest:tr.html.twig', array(
			'course' => $course,
			'testPaperId' => $testPaperId,
			'item' => $item,
			'questions' => $questions,
			'testPaper' => $testPaper,
			'lessons' => $lessons,
		));
	}

	public function quesitonNumberCheckAction(Request $request, $courseId)
    {
		$course = $this->getCourseService()->tryManageCourse($courseId);

        if (empty($course)) {
            throw $this->createNotFoundException();
        }

        //$itemScores = $request->request->get('itemScores');
        //$perventage = $request->request->get('perventage');
        $isDiffculty = $request->request->get('isDiffculty');
        $itemCounts = ArrayToolkit::index($request->request->get('itemCounts'), '0');
		
		$perventage['simple']     = 10;
		$perventage['ordinary']   = 40;
		$perventage['difficulty'] = 50;

        $questionNumbers = $this->getQuestionService()->getQuestionsNumberByCourseId($courseId);
        echo "<pre>";var_dump($questionNumbers);header('Content-type:text/html;charset=utf-8');echo "</pre>";
        if(($perventage['simple'] + $perventage['ordinary'] +$perventage['difficulty']) != 100){
        	throw $this->createNotFoundException('参数错误');
        }

        $dictQuestionType = $this->getWebExtension()->getDict('questionType');
		$dictDifficulty   = $this->getWebExtension()->getDict('difficulty');

        $message = array();

        foreach ($itemCounts as $key => $item) {
        	$num = (int) $item['1'];

        	if ($num == 0){
        		continue;
        	}

        	if ($isDiffculty == 1 ){
        		$thisNums = array();
				$thisNums['simple']     = (int) ($num * $perventage['simple'] /100); 
				$thisNums['ordinary']   = (int) ($num * $perventage['ordinary'] /100); 
				$thisNums['difficulty'] = (int) ($num * $perventage['difficulty'] /100); 

	        	$otherNum = $num - ($thisNums['simple'] + $thisNums['ordinary'] + $thisNums['difficulty']);

	        	if ($otherNum != 0){
	        		$randNum = array_rand($thisNums, 1);
	        		$thisNums[$randNum] = $thisNums[$randNum] + $otherNum;
	        	}

	        	foreach ($thisNums as $k => $thisNum) {
	        		if(empty($questionNumbers[$key][$k])) {

	        			if($thisNum != 0)
	        				$message[] = "{$dictQuestionType[$key]}中{$dictDifficulty[$k]}缺少{$thisNum}题 <br>";
	        		}
	        		else if(($thisNum - $questionNumbers[$key][$k]) < 0) {

	        			$thisNum = abs($thisNum - $questionNumbers[$key][$k]);
	        			$message[] = "{$dictQuestionType[$key]}中{$dictDifficulty[$k]}缺少{$thisNum}题 <br>";
	        		}
	        	}
        	} else {
        		$typeNumber = 0;
        		if(empty($questionNumbers[$key])){

        			$message[] = "{$dictQuestionType[$key]}缺少{$num}题 <br>";

        		}else{

        			foreach ($questionNumbers[$key] as $questionNumber) {

	        			$typeNumber = $questionNumber + $typeNumber;
	        		}
	        		if( ($typeNumber - $num) < 0) {
	        			$thisNum = abs($typeNumber - $num);
	        			$message[] = "{$dictQuestionType[$key]}缺少{$thisNum}题 <br>";
	        		}
        		}

        	}

        	if(empty($message)){
        		$message = false;
        	}
        }

        return $this->createJsonResponse($message);
    }
    public function deleteItemAction(Request $request, $courseId, $testItemId)
    {
		$course = $this->getCourseService()->tryManageCourse($courseId);
        $item = $this->getTestService()->getTestItem($testItemId);
        if (empty($item)) {
            throw $this->createNotFoundException();
        }
        $this->getTestService()->deleteItem($testItemId);
        return $this->createJsonResponse(true);
    }

    public function deleteItemsAction(Request $request, $courseId)
    {   
		$course = $this->getCourseService()->tryManageCourse($courseId);
        $ids = $request->request->get('ids');
        if(empty($ids)){
        	throw $this->createNotFoundException();
        }
        foreach ($ids as $id) {
        	$this->getTestService()->deleteItem($id);
        }
        return $this->createJsonResponse(true);
    }

	private function getCourseService()
    {
        return $this->getServiceKernel()->createService('Course.CourseService');
    }

   	private function getQuestionService()
   	{
   		return $this -> getServiceKernel()->createService('Quiz.QuestionService');
   	}

   	private function getTestService()
   	{
   		return $this -> getServiceKernel()->createService('Quiz.TestService');
   	}

   	private function getWebExtension()
    {
        return $this->container->get('topxia.twig.web_extension');
    }

}