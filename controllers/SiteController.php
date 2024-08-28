<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use yii\web\UploadedFile;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\UploadForm; // Подключаем модель для загрузки файла

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $model = new UploadForm();

        if (Yii::$app->request->isPost) {
            $model->file = UploadedFile::getInstance($model, 'file');
            if ($model->validate()) {
                try {
                    $data = $this->parseHtml($model->file->tempName);
                    if (empty($data)) {
                        Yii::$app->session->setFlash('error', 'Не удалось найти данные в файле. Проверьте его структуру.');
                        return $this->render('index', ['model' => $model]);
                    }
                    $balanceData = $this->calculateBalance($data);
                    return $this->render('chart', ['balanceData' => $balanceData]);
                } catch (\Exception $e) {
                    Yii::$app->session->setFlash('error', 'Произошла ошибка при обработке файла: ' . $e->getMessage());
                }
            } else {
                Yii::$app->session->setFlash('error', 'Загруженный файл не прошел проверку.');
            }
        }

        return $this->render('index', ['model' => $model]);
    }


    /**
     * Парсинг HTML-файла для извлечения данных о прибыли.
     *
     * @param string $filePath
     * @return array
     */
    private function parseHtml($filePath)
    {
        $data = [];
        $dom = new \DOMDocument;
        @$dom->loadHTMLFile($filePath);
        $rows = $dom->getElementsByTagName('tr');

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            if ($cells->length > 0) {
                $transactionType = null;
                $lastValue = null;

                foreach ($cells as $cell) {
                    $cellValue = trim($cell->nodeValue);

                    // Определяем тип транзакции (buy или balance)
                    if ($cellValue === 'buy' || $cellValue === 'balance') { //  меня очень сильно смущает что balance
                        $transactionType = $cellValue;
                    }

                    // Ищем последнее числовое значение
                    $numericValue = str_replace([' ', ','], ['', '.'], $cellValue);
                    if (is_numeric($numericValue)) {
                        $lastValue = (float)$numericValue;
                    }
                }

                // Если нашли тип транзакции и последнее значение, добавляем в массив данных
                if ($transactionType !== null && $lastValue !== null) {
                    $data[] = $lastValue;
                }
            }
        }

        return $data;
    }




    /**
     * Расчет баланса на основе массива данных о прибыли.
     *
     * @param array $data
     * @return array
     */
    private function calculateBalance($data)
    {
//        echo '<pre>';
//        print_r($data);exit;
        $balance = 0;
        $balanceData = [];
        foreach ($data as $profit) {
            $balance += $profit;
            if ($balance < 0) {
                $balance = 0;
            }
            $balanceData[] = $balance;
        }
        return $balanceData;
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }
}
