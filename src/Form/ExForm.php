<?php

namespace Drupal\myform\Form;

use Drupal\Core\Form\FormBase;                   // Базовый класс Form API
use Drupal\Core\Form\FormStateInterface;              // Класс отвечает за обработку данных

/**
 * Наследуемся от базового класса Form API
 * @see \Drupal\Core\Form\FormBase
 */
class ExForm extends FormBase
{

    // метод, который отвечает за саму форму - кнопки, поля
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['first_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Firs name'),
            '#required' => TRUE,
            '#size'=>30,
        ];
        $form['last_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Last name'),
            '#required' => TRUE,
            '#size'=>30,
        ];

        $form['subject'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Subject'),
            '#required' => TRUE,
            '#size'=>30,
        ];

        $form['message'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Message'),
            '#required' => TRUE,
        ];

        $form['email'] = array(
            '#type' => 'email',
            '#title' => $this->t('E-mail'),
            '#description' => $this->t('Enter Your Email.'),
            '#placeholder' => $this->t('your e-mail'),
            '#required' => TRUE,
            '#size'=>30,
        );

        // Add a submit button that handles the submission of the form.
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Отправить форму'),
        ];

        $form['actions']['create'] = [
            '#type' => 'submit',
            '#value' => $this->t('Добавить контакт в хостаб'),
            '#submit' => ['::createForm'],
        ];

        return $form;
    }

    // метод, который будет возвращать название формы
    public function getFormId()
    {
        return 'ex_form_exform_form';
    }

    // ф-я валидации
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $title = $form_state->getValue('title');
        $is_number = preg_match("/[\d]+/", $title, $match);

        if ($is_number > 0) {
            $form_state->setErrorByName('title', $this->t('Строка содержит цифру.'));
        }
    }

    public function  createForm(array &$form, FormStateInterface $formState){

        $data = $formState->getValues();

        $arr = array(
            'properties' => array(
                array(
                    'property' => 'email',
                    'value' => $data['email']
                ),
                array(
                    'property' => 'firstname',
                    'value' => $data['first_name']
                ),
                array(
                    'property' => 'lastname',
                    'value' => $data['last_name']
                )
            )
        );
        $post_json = json_encode($arr);
        $hapikey = '23db15b6-de10-4439-b9b6-b24b5aca5371';
        $endpoint = 'https://api.hubapi.com/contacts/v1/contact?hapikey=' . $hapikey;
        $ch = @curl_init();
        @curl_setopt($ch, CURLOPT_POST, true);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $post_json);
        @curl_setopt($ch, CURLOPT_URL, $endpoint);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = @curl_exec($ch);
        $status_code = @curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_errors = curl_error($ch);
        @curl_close($ch);
        drupal_set_message("Результат запроса:");
        if($curl_errors)
            drupal_set_message($curl_errors);
        else{
            if($status_code!='200') {
                drupal_set_message('status code: '.$status_code);
                drupal_set_message(json_decode($response,true)['message']);
            }else
                drupal_set_message('Контакт добавлен!');
        }
    }

    // действия по сабмиту
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $mailManager = \Drupal::service('plugin.manager.mail');
        $module = 'myform';
        $key = 'node_insert';
        $to = $form_state->getValue('email');
        $params['title'] = $form_state->getValue('subject');
        $params['message'] = $form_state->getValue('message');
        $langcode = \Drupal::currentUser()->getPreferredLangcode();

        $result = $mailManager->mail($module, $key, $to, $langcode, $params, NULL, true);
        if ($result['result'] != true) {
            $message = t('Возникли проблемы отправки почты на @email.', array('@email' => $to));
            drupal_set_message($message, 'error');
            return;
        }
        $message = t('Сообщение отправлено на @email ', array('@email' => $to));
        drupal_set_message($message);
        \Drupal::logger('mail')->notice('___message: '.$params['message']." ___e-mail: ".$to);
    }

}
