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
    function parseHtml($filePath)
    {
        $data = [];
        $dom = new \DOMDocument;
        @$dom->loadHTMLFile($filePath);
        $rows = $dom->getElementsByTagName('tr');

        $isInsideTable = false;
        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            // Проверяем начало таблицы
            if ($cells->length == 14 && strpos($row->textContent, 'Close Time') !== false) {
                $isInsideTable = true;
                continue;
            }

            // Проверяем конец таблицы
            if ($cells->length == 14 && strpos($row->textContent, 'Close Time') === false && strpos($row->textContent, 'Open Time') !== false) {
                $isInsideTable = false;
                continue;
            }

            // Если находимся внутри таблицы, обрабатываем строки
            if ($isInsideTable && $cells->length >= 5) {
                // Проверка на наличие лишних строк
                $firstCellContent = trim($cells->item(0)->nodeValue);
                $hasColspan = $cells->item(0)->hasAttribute('colspan');

                // Исключаем строки с colspan и пустым значением
                if ($hasColspan && $firstCellContent === '&nbsp;') {
                    continue;
                }

                // Исключаем строки, которые имеют только пустые или ненужные данные
                $isRowEmpty = true;
                for ($i = 0; $i < $cells->length; $i++) {
                    $cellContent = trim($cells->item($i)->nodeValue);
                    if (!empty($cellContent) && $cellContent !== '&nbsp;' && is_numeric(str_replace([' ', ','], ['', '.'], $cellContent))) {
                        $isRowEmpty = false;
                        break;
                    }
                }

                if ($isRowEmpty) {
                    continue;
                }

                // Проверка содержимого последней ячейки
                $lastCellValue = trim($cells->item($cells->length - 1)->nodeValue);
                $numericValue = str_replace([' ', ','], ['', '.'], $lastCellValue);

                // Убедимся, что значение числовое
                if (is_numeric($numericValue)) {
                    $data[] = (float)$numericValue;
                }
            }
        }

        // Удаляем последнее значение, если нужно
        array_pop($data);

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
