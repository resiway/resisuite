<?php
namespace config;

// maximum number of categories a user is allowed to post daily
define('RESIWAY_CATEGORIES_DAILY_MAX', 30);


define('RESIWAY_CATEGORY_TITLE_LENGTH_MIN', 1);
define('RESIWAY_CATEGORY_TITLE_LENGTH_MAX', 64);

define('RESIWAY_CATEGORY_DESCRIPTION_LENGTH_MIN', 0);
define('RESIWAY_CATEGORY_DESCRIPTION_LENGTH_MAX', 255);


/*
* Contrainsts for user activity.
* These limitations should be way beyond a normal human activity...
*/
// maximum number of questions a user is allowed to post daily
define('RESIEXCHANGE_QUESTIONS_DAILY_MAX', 100);
// maximum number of answers a user is allowed to post daily
define('RESIEXCHANGE_ANSWERS_DAILY_MAX', 100);
// maximum number of comments a user is allowed to post daily
define('RESIEXCHANGE_COMMENTS_DAILY_MAX', 100);




define('RESIEXCHANGE_QUESTION_VOTEUP_DAILY_MAX', 50);
define('RESIEXCHANGE_ANSWER_VOTEUP_DAILY_MAX', 50);
define('RESIEXCHANGE_COMMENT_VOTEUP_DAILY_MAX', 50);

define('RESIEXCHANGE_QUESTION_VOTEDOWN_DAILY_MAX', 50);
define('RESIEXCHANGE_ANSWER_VOTEDOWN_DAILY_MAX', 50);
define('RESIEXCHANGE_COMMENT_VOTEDOWN_DAILY_MAX', 50);

define('RESIEXCHANGE_QUESTION_FLAG_DAILY_MAX', 50);
define('RESIEXCHANGE_ANSWER_FLAG_DAILY_MAX', 50);
define('RESIEXCHANGE_COMMENT_FLAG_DAILY_MAX', 50);


/* 
* Contraints for new objects creation.
* Let's try not being too restrictive here, 
* the idea beign to let the community deal with everyone's behavior 
*/
define('RESIEXCHANGE_QUESTION_TITLE_LENGTH_MIN', 23);
define('RESIEXCHANGE_QUESTION_TITLE_LENGTH_MAX', 128);
define('RESIEXCHANGE_QUESTION_CONTENT_LENGTH_MIN', 0);
define('RESIEXCHANGE_QUESTION_CONTENT_LENGTH_MAX', 4096);
define('RESIEXCHANGE_QUESTION_CATEGORIES_COUNT_MIN', 1);
define('RESIEXCHANGE_QUESTION_CATEGORIES_COUNT_MAX', 8);

define('RESIEXCHANGE_ANSWER_CONTENT_LENGTH_MIN', 32);
define('RESIEXCHANGE_ANSWER_CONTENT_LENGTH_MAX', 16384);


define('RESIEXCHANGE_ANSWER_CONTENT_EXCERPT_LENGTH_MAX', 200);


// default app for this package
define('DEFAULT_APP', 'resiway.fr');