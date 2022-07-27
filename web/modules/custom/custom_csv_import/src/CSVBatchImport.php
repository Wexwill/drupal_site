<?php

namespace Drupal\custom_csv_import;

# Нам необходимо также импортировать объект File для работы с CSV файлом.
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class CSVBatchImport.
 *
 * @package Drupal\custom_csv_import
 */
class CSVBatchImport {
  # Здесь мы будем хранить всю информацию о нашей Batch операции.
  private $batch;

  # FID для CSV файла.
  private $fid;

  # Объект файла.
  private $file;

  # Мы также добавим возможность игнорировать первую строку у csv файла,
  # которая может использоваться для заголовков столбцов.
  # По умолчанию первая линия будет считываться и обрабатываться.
  private $skip_first_line;

  # Разделитель столбцов CSV.
  private $delimiter;

  # Ограничитель поля CSV.
  private $enclosure;

  /**
   * {@inheritdoc}
   */
  public function __construct($fid, $skip_first_line = FALSE, $delimiter = ';', $enclosure = ',', $batch_name = 'Custom CSV import') {
    $this->fid = $fid;
    $this->file = File::load($fid);
    $this->skip_first_line = $skip_first_line;
    $this->delimiter = $delimiter;
    $this->enclosure = $enclosure;
    $this->batch = [
      'title' => $batch_name,
      'finished' => [$this, 'finished'],
      'file' => \Drupal::service('extension.list.module')->getPath('custom_csv_import') . '/src/CSVBatchImport.php',
    ];
    $this->parseCSV();
  }

  /**
   * {@inheritdoc}
   *
   * В данном методе мы подготавливаем операции для импорта. В него мы будем
   * передавать данные из CSV файла в виде массива. Каждая строка из файла
   * будет добавлена в операцию данным методом.
   */
  public function setOperation($data) {
    # Первым параметром операции передается callback который будет вызван для
    # обработки данной операции, а вторым - массив переменных которые будут
    # переданы операции на обработку. Так как мы работаем из объекта, то мы
    # в первом параметре передаем массив из самого объекта и названия метода
    # который будет вызван для обработки операции.
    $this->batch['operations'][] = [[$this, 'processItem'], $data];
  }

  /**
   * {@inheritdoc}
   * Метод для регистрации нашей batch операции, которую мы подготовили.
   */
  public function setBatch() {
    batch_set($this->batch);
  }

  /**
   * {@inheritdoc}
   * Метод для ручного запуска выполнения batch операций.
   */
  public function processBatch() {
    batch_process();
  }

  /**
   * {@inheritdoc}
   *
   * Обработка элемента (строки из файла). В соответствии со столбцами и их
   * порядком мы получаем их данные в переменные. И не забываем про $context.
   */
  public function processItem($id, $title, $body, $tags, &$context) {
    # Если указан id, значит мы правим ноду а не создаем.
    if (!empty($id)) {
      $node = Node::load($id);
    }
    else {
      $node = Node::create([
        'type' => 'article',
        'langcode' => 'ru',
        'uid' => 1,
        'status' => 1,
      ]);
    }

    $node->title = $title;
    $node->body = [
      'value' => $body,
      'format' => 'full_html',
    ];
    # Так как мы можем задать несколько тегов через запятую в файле, нам их
    # необходимо обработать соответствующим образом.
    $tags_array = explode(',', $tags);
    # Теги в поле хранятся не по названию, а по TID, следовательно, нам придется
    # для каждого тега найти его ID, а в этот массив мы будем записывать их
    # значения;
    $tags_ids = [];
    foreach ($tags_array as $k => $v) {
      $query = \Drupal::entityQuery('taxonomy_term');
      $query->condition('vid', 'tags');
      $query->condition('name', $v);
      $query->range(0, 1);
      $result = $query->execute();
      $tid = reset($result);

      # Если термин с таким названием нашелся - просто добавляем.
      if ($tid) {
        $tags_ids[] = $tid;
      }
      # Иначе создаем новый термин.
      else {
        $term = Term::create([
          'name' => $v,
          'vid' => 'tags',
        ]);
        $term->save();
        $tags_ids[] = $term->tid->value;
      }
    }
    $node->field_tags = $tags_ids;
    $node->save();

    # Записываем результат в общий массив результатов batch операции. По этим
    # данным мы будем выводить кол-во импортированных данных.
    $context['results'][] = $node->id() . ' : ' . $node->label();
    $context['message'] = $node->label();
  }

  /**
   * {@inheritdoc}
   *
   * Метод который будет вызван по окончанию всех batch операций, или в случае
   * возникновения ошибки в процессе.
   */
  public function finished($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()
        ->formatPlural(count($results), 'One post processed.', '@count posts processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    $messenger= \Drupal::messenger();
    $messenger->addMessage($message);
  }

  /**
   * {@inheritdoc}
   *
   * В данном методе мы обрабатываем наш CSV строка за строкой, а не грузим
   * весь файл в память, так что данный способ значительно менее затратный
   * и более шустрый.
   *
   * Каждую строку мы получаем в виде массива, а массив передаем в операцию на
   * выполнение.
   */
  public function parseCSV() {
    if (($handle = fopen($this->file->getFileUri(), 'r')) !== FALSE) {
      # Если необходимо пропустить первую строку csv файла, то мы просто в
      # холостую грузим и ничего не делаем с ней.
      if ($this->skip_first_line) {
        fgetcsv($handle, 0, ';');
      }
      while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
        $this->setOperation($data);
      }
      fclose($handle);
    }
  }
}
