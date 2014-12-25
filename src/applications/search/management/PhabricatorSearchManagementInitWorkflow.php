<?php

final class PhabricatorSearchManagementInitWorkflow
  extends PhabricatorSearchManagementWorkflow {

  protected function didConstruct() {
    $this
      ->setName('init')
      ->setSynopsis('Initialize or repair an index.')
      ->setExamples('**init**');
  }

  public function execute(PhutilArgumentParser $args) {
    $console = PhutilConsole::getConsole();

    $engine = PhabricatorSearchEngineSelector::newSelector()->newEngine();

    $work_done = false;
    if (!$engine->indexExists()) {
      $console->writeOut(
        '%s',
        pht('Index does not exist, creating...'));
      $engine->initIndex();
      $console->writeOut(
        "%s\n",
        pht('done.'));
      $work_done = true;
    } else if (!$engine->indexIsSane()) {
      $console->writeOut(
        '%s',
          pht('Index exists but is incorrect, fixing...'));
      $engine->initIndex();
      $console->writeOut(
        "%s\n",
        pht('done.'));
      $work_done = true;
    }

    if ($work_done) {
      $console->writeOut(
        "%s\n",
        pht('Index maintenance complete. Run `./bin/search index` to '.
            'reindex documents'));
    } else {
      $console->writeOut(
        "%s\n",
        pht('Nothing to do.'));
    }
  }
}
