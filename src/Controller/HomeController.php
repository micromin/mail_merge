<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request; 
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Bundle\SwiftmailerBundle\Swift_SmtpTransport;

class HomeController extends AbstractController
{
	/**
     * @Route("/reset", name="reset")
     */
    public function reset()
    {
    	unlink($this->getParameter('temp_dir').'/'.'list.csv');
    	return $this->redirectToRoute('home');
    }

    /**
     * @Route("/", name="home")
     */
    public function index()
    {
    	$errors = [];
    	$serializer = $this->container->get('serializer');
    	$line_of_text = [];
    	$columns = [];
    	try {
                $file_handle = fopen($this->getParameter('temp_dir').'/'.'list.csv', 'r');
                while (!feof($file_handle) ) {
                	$line = fgetcsv($file_handle, 1024);
                	if (!empty($line)){
			        	$line_of_text[] = $line;
			    	}
			    }
			    $columns = $line_of_text[0];
			    $line_of_text = array_splice($line_of_text, 1, sizeof($line_of_text) -1);
            } catch (\Exception $e) {
               
            }
    	$formUpload = $this->createFormBuilder()
            ->add('csvFile', FileType::class, array('required' => false, 'mapped' => true, 'attr' => [
                'accept' => '*.*'
            ]))
            ->getForm();
        return $this->render('home/index.html.twig', [
        	'name' => '',
        	'email' => '',
        	'password' => '',
        	'message' => '',
        	'subject' => '',
        	'rows' => $serializer->serialize($line_of_text, 'json'),
        	'form' => $formUpload->createView(),
        	'rowData' => $line_of_text,
        	'columns' => $columns,
        	'template' => '',
        	'errors' => $errors,
        	'sent' => 0
        ]);
    }

    /**
     * @Route("/preview", name="preview", methods={"POST", "GET"})
     */
    public function preview(Request $request)
    {	

    	$errors = [];
    	$data = $request->request->all();
		$serializer = $this->container->get('serializer');

		$formUpload = $this->createFormBuilder()
            ->add('csvFile', FileType::class, array('required' => false, 'mapped' => true, 'attr' => [
                'accept' => '.csv'
            ]))
            ->getForm();
        $formUpload->handleRequest($request);
		$file = $formUpload['csvFile']->getData();
		$line_of_text = [];
		$columns = [];
		if ($file){
			try {
                $file->move($this->getParameter('temp_dir'), 'list.csv');
                $file_handle = fopen($this->getParameter('temp_dir').'/'.'list.csv', 'r');
                while (!feof($file_handle) ) {
			        $line_of_text[] = fgetcsv($file_handle, 1024);
			    }
			    fclose($file_handle);
 				$columns = $line_of_text[0];
			    $columns[] = 'already_sent';
			    $line_of_text = array_splice($line_of_text, 1, sizeof($line_of_text) -1);
				$file_handle = fopen($this->getParameter('temp_dir').'/'.'list.csv',"w");
				fputcsv($file_handle, $columns);
				foreach ($line_of_text as $index=>$line){
				  $line[]='No';
				  fputcsv($file_handle, $line);
				  $line_of_text[$index] = $line;
				}
				fclose($file_handle);

            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
		}else {
			try {
                $file_handle = fopen($this->getParameter('temp_dir').'/'.'list.csv', 'r');
                while (!feof($file_handle) ) {
                	$line = fgetcsv($file_handle, 1024);
                	if (!empty($line)){
			        	$line_of_text[] = $line;
			    	}
			    }
			    $columns = $line_of_text[0];
			    $line_of_text = array_splice($line_of_text, 1, sizeof($line_of_text) -1);
            } catch (\Exception $e) {
               $errors[] = $e->getMessage();
            }
		}

		$template = "";
		if ($request->getMethod() == 'POST' && sizeof($line_of_text) > 0){
			if (!empty($data) && isset($data['message'])){
				$th = $this->get('twig')->createTemplate($data['message']);
				$myvalues = [];
				foreach ($columns as $index => $value) {
					$myvalues[$value]= $line_of_text[0][$index];
				}
				try {
					$template = $th->render($myvalues);
				}catch(\Exception $e){
					$errors[] = $e->getMessage();
				}
			}
		}
		if ($request->getMethod() == 'POST'){
			$this->get('session')->set('data', $serializer->serialize($data, 'json'));
		}else {
			$data = json_decode($this->get('session')->get('data'), true);
		}
		$messages = [];
		$sent = 0;
		if (!empty($data['reset'])){
			$this->get('session')->set('data', '');
			$data = [];
		}else if (!empty($data['send'])){
			$name = !empty($data) ? $data['name'] : '';
			$subject = !empty($data) ? $data['subject'] : '';
			$email = !empty($data) ? $data['email'] : '';
			$password = !empty($data) ? $data['password'] : '';

			if (empty($name)){
				throw new \Exception("Nams is empty.", 1);
			}
			if (empty($subject)){
				throw new \Exception("Subject is empty.", 1);
			}
			if (empty($email)){
				throw new \Exception("Email is empty.", 1);
			}
			if (empty($password)){
				throw new \Exception("Password is empty.", 1);
			}
			if (empty($data['message'])){
				throw new \Exception("Message is empty.", 1);
			}

			foreach ($line_of_text as $rowId => $row) {
				$th = $this->get('twig')->createTemplate($data['message']);
				$myvalues = [];
				foreach ($columns as $index => $value) {
					$myvalues[$value]= $row[$index];
				}

				$transport = (new \Swift_SmtpTransport('smtp.gmail.com', 465, 'ssl'))
				  ->setUsername($email)
				  ->setPassword($password);

				$mailer = new \Swift_Mailer($transport);

				$emailIndex = array_search('email', $columns);
				$sentIndex = array_search('already_sent', $columns);
				$customEmail = $row[$emailIndex];
				$customMessage = '';
				try {
					$customMessage = $th->render($myvalues);
					$message = (new \Swift_Message($subject))
				        ->setFrom([$email => $name])
				        ->setTo($customEmail)
				        ->setBody(
				            $customMessage,
				            'text/html'
				        );
				    if ($mailer->send($message)){
						$row[$sentIndex] = 'Yes';
						$line_of_text[$rowId] = $row;
						$sent+=1;
				    }
				}catch(\Exception $e){
					dump($e);
					die();
					break;
				}
			}
			$file_handle = fopen($this->getParameter('temp_dir').'/'.'list.csv',"w");
			fputcsv($file_handle, $columns);
			foreach ($line_of_text as $index=>$line){
				fputcsv($file_handle, $line);
			}
			fclose($file_handle);
		}
		
        return $this->render('home/index.html.twig', [
            'preview' => true,
            'form' => $formUpload->createView(),
            'name' => !empty($data) && isset($data['name']) ? $data['name'] : '',
            'email' => !empty($data) && isset($data['email']) ? $data['email'] : '',
            'message' => !empty($data) && isset($data['message']) ? $data['message'] : '',
            'subject' => !empty($data) && isset($data['subject']) ? $data['subject'] : '',
            'password' => !empty($data) && isset($data['password']) ? $data['password'] : '',
            'rows' => $serializer->serialize($line_of_text, 'json'),
            'rowData' => $line_of_text,
            'columns' => $columns,
            'template' => $template,
            'errors' => $errors,
            'sent' => $sent
        ]);
    }
}
