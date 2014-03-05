<?php 
namespace Application\Controller;

use Zend\Config\Reader\Json;

use Zend\XmlRpc\Value\ArrayValue;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class BookController extends AbstractRestfulController
{	
	public function indexAction(){
		
	}
	
	public function listAction()
	{
		$em = $this->getServiceLocator()
		->get('doctrine.entitymanager.orm_default');		 
		$books = $em->createQueryBuilder()
		->select('r')
		->from('Application\Entity\BookLibrary','r');	 
		return new JsonModel($books->getQuery()->getArrayResult());
	}
	
	public function getAction()
	{
		$request = $this->getRequest()->getQuery();
		$em = $this->getServiceLocator()
		->get('Doctrine\ORM\EntityManager');
		$books = $em->createQueryBuilder()
		->select('r')
		->from('Application\Entity\BookLibrary','r')
		->where('r.id = :id')
		->setParameter('id', $request->book_id);
		return new JsonModel($books->getQuery()->getArrayResult());
	}
	
    public function postAction()
    {
    	$params = json_decode($this->getRequest()->getContent());
    	$em = $this->getServiceLocator()
    			   ->get('Doctrine\ORM\EntityManager');
    	$book = new \Application\Entity\BookLibrary();
    	$book->setTitle($params->title);
    	$book->setAuthor($params->author);
    	$book->setKeywords($params->keywords);
    	$book->setReleaseDate(new \DateTime($params->releaseDate));
    	$em->persist($book);
    	$em->flush();
    	$bookId = $book->getId();
    	$releaseDate = $book->getReleaseDate();
    	return new JsonModel(array('id' => $bookId,'releaseDate' => $releaseDate));
    }
    
    public function deleteAction()
    {
    	$request = $this->getRequest()->getQuery();
    	$em = $this->getServiceLocator()
    			   ->get('Doctrine\ORM\EntityManager');
    	$book = $em->getRepository('\Application\Entity\BookLibrary')
	    		   ->find($request->book_id);
    	$em->remove($book);
    	$em->flush();
    	return new JsonModel(array('deleted' => 'yes'));
    }
    
    public function putAction()
    {
    	$params = json_decode($this->getRequest()->getContent());
    	$em = $this->getServiceLocator()
    			   ->get('Doctrine\ORM\EntityManager');
    	$book = new \Application\Entity\BookLibrary;
    	$book = $em->getRepository('\Application\Entity\BookLibrary')->find($params->id);
    	$book->setTitle($params->title);
    	$book->setAuthor($params->author);
    	$book->setKeywords($params->keywords);
    	$book->setReleaseDate(new \DateTime($params->releaseDate));
    	$em->persist($book);
    	$em->flush();
    	return new JsonModel(array('id' => $params->id));
    }
    
    
}
?>