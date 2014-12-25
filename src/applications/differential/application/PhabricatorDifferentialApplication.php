<?php

final class PhabricatorDifferentialApplication extends PhabricatorApplication {

  public function getBaseURI() {
    return '/differential/';
  }

  public function getName() {
    return pht('Differential');
  }

  public function getShortDescription() {
    return pht('Review Code');
  }

  public function getIconName() {
    return 'differential';
  }

  public function isPinnedByDefault(PhabricatorUser $viewer) {
    return true;
  }

  public function getHelpURI() {
    return PhabricatorEnv::getDoclink('Differential User Guide');
  }

  public function getFactObjectsForAnalysis() {
    return array(
      new DifferentialRevision(),
    );
  }

  public function getTitleGlyph() {
    return "\xE2\x9A\x99";
  }

  public function getEventListeners() {
    return array(
      new DifferentialActionMenuEventListener(),
      new DifferentialHovercardEventListener(),
      new DifferentialLandingActionMenuEventListener(),
    );
  }

  public function getOverview() {
    return pht(<<<EOTEXT
Differential is a **code review application** which allows engineers to review,
discuss and approve changes to software.
EOTEXT
);
  }

  public function getRoutes() {
    return array(
      '/D(?P<id>[1-9]\d*)' => 'DifferentialRevisionViewController',
      '/differential/' => array(
        '(?:query/(?P<queryKey>[^/]+)/)?'
          => 'DifferentialRevisionListController',
        'diff/' => array(
          '(?P<id>[1-9]\d*)/' => 'DifferentialDiffViewController',
          'create/' => 'DifferentialDiffCreateController',
        ),
        'changeset/' => 'DifferentialChangesetViewController',
        'revision/edit/(?:(?P<id>[1-9]\d*)/)?'
          => 'DifferentialRevisionEditController',
        'revision/land/(?:(?P<id>[1-9]\d*))/(?P<strategy>[^/]+)/'
          => 'DifferentialRevisionLandController',
        'revision/closedetails/(?P<phid>[^/]+)/'
          => 'DifferentialRevisionCloseDetailsController',
        'comment/' => array(
          'preview/(?P<id>[1-9]\d*)/' => 'DifferentialCommentPreviewController',
          'save/(?P<id>[1-9]\d*)/' => 'DifferentialCommentSaveController',
          'inline/' => array(
            'preview/(?P<id>[1-9]\d*)/'
              => 'DifferentialInlineCommentPreviewController',
            'edit/(?P<id>[1-9]\d*)/'
              => 'DifferentialInlineCommentEditController',
          ),
        ),
        'preview/' => 'PhabricatorMarkupPreviewController',
      ),
    );
  }

  public function getApplicationOrder() {
    return 0.100;
  }

  public function getRemarkupRules() {
    return array(
      new DifferentialRemarkupRule(),
    );
  }

  public function loadStatus(PhabricatorUser $user) {
    $revisions = id(new DifferentialRevisionQuery())
      ->setViewer($user)
      ->withResponsibleUsers(array($user->getPHID()))
      ->withStatus(DifferentialRevisionQuery::STATUS_OPEN)
      ->needRelationships(true)
      ->setLimit(self::MAX_STATUS_ITEMS)
      ->execute();

    $status = array();
    if (count($revisions) == self::MAX_STATUS_ITEMS) {
      $all_count = count($revisions);
      $all_count_str = self::formatStatusCount(
        $all_count,
        '%s Active Reviews',
        '%d Active Review(s)');
      $type = PhabricatorApplicationStatusView::TYPE_WARNING;
      $status[] = id(new PhabricatorApplicationStatusView())
        ->setType($type)
        ->setText($all_count_str)
        ->setCount($all_count);
    } else {
      list($blocking, $active, $waiting) =
        DifferentialRevisionQuery::splitResponsible(
          $revisions,
          array($user->getPHID()));

      $blocking = count($blocking);
      $blocking_str = self::formatStatusCount(
        $blocking,
        '%s Reviews Blocking Others',
        '%d Review(s) Blocking Others');
      $type = PhabricatorApplicationStatusView::TYPE_NEEDS_ATTENTION;
      $status[] = id(new PhabricatorApplicationStatusView())
        ->setType($type)
        ->setText($blocking_str)
        ->setCount($blocking);

      $active = count($active);
      $active_str = self::formatStatusCount(
        $active,
        '%s Reviews Need Attention',
        '%d Review(s) Need Attention');
      $type = PhabricatorApplicationStatusView::TYPE_WARNING;
      $status[] = id(new PhabricatorApplicationStatusView())
        ->setType($type)
        ->setText($active_str)
        ->setCount($active);

      $waiting = count($waiting);
      $waiting_str = self::formatStatusCount(
        $waiting,
        '%s Reviews Waiting on Others',
        '%d Review(s) Waiting on Others');
      $type = PhabricatorApplicationStatusView::TYPE_INFO;
      $status[] = id(new PhabricatorApplicationStatusView())
        ->setType($type)
        ->setText($waiting_str)
        ->setCount($waiting);
    }

    return $status;
  }

  protected function getCustomCapabilities() {
    return array(
      DifferentialDefaultViewCapability::CAPABILITY => array(
        'caption' => pht('Default view policy for newly created revisions.'),
      ),
    );
  }

}
