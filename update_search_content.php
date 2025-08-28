<?php

require_once 'vendor/autoload.php';

use App\Models\KnowledgeBase;

$app = require_once 'bootstrap/app.php';
$app->boot();

echo "Updating search content for Knowledge Base entries...\n";

$entries = KnowledgeBase::all();
$count = 0;

foreach ($entries as $kb) {
  $searchContent = strip_tags($kb->title . ' ' . $kb->content . ' ' . $kb->excerpt);
  $kb->update(['search_content' => $searchContent]);
  $count++;
  echo "Updated entry: {$kb->title}\n";
}

echo "Successfully updated {$count} entries.\n";
