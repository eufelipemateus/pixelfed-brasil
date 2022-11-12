<?php

return [

	'helpcenter' => 'Help Center',
	'whatsnew' => 'What\'s New',

	'gettingStarted' => 'Getting Started',
	'sharingMedia' => 'Sharing Media',
	'profile' => 'Profile',
	'stories' => 'Stories',
	'hashtags' => 'Hashtags',
	'discover' => 'Discover',
	'directMessages' => 'Direct Messages',
	'timelines' => 'Timelines',
	'embed'	=> 'Embed',

	'communityGuidelines' => 'Community Guidelines',
	'whatIsTheFediverse' => 'What is the fediverse?',
	'controllingVisibility' => 'Controlling Visibility',
	'blockingAccounts' => 'Blocking Accounts',
	'safetyTips' => 'Safety Tips',
	'reportSomething' => 'Report Something',
	'dataPolicy' => 'Data Policy',

	'taggingPeople' => 'Tagging People',


	'howAccount' =>	'How can I create an account?',
	'howBio' => 'How can I change my bio?',

	'whatHahstag' =>'What are hashtags?',
	'hashtagsTips' => 'Hashtags Tips',

	'howPost' => 'How do I create a post?',
	'howFilter' => 'How do I add a filter?',

	'whatDiscover' =>	'What is Discover?',
	'whatDiscoverCat' => 'What are Discover Categories?',

	'howPrivate' => 'How can I make my account private?',
	'howSecure' =>'How can I secure my account?',

	'howDirect' => 'How do I use Pixelfed Direct?',
	'hoUnsend' => 'How do I unsend a message?',

	'personal' => 'Personal Timeline',
	'public' => 'Public Timeline',

	'contentRemoved' => 'Content that will be removed',
	'contentExplicitly' => 'Content that is explicitly disallowed',

	'knowRules' =>'Know the rules',
	'make3Post' => 'Make your account or posts private',

	'welcomePiexelfed' => 'Welcome to '.config_cache('app.name').'!',

	'howCreateAccountAask' =>'How do I create a Pixelfed account?',
	'howCreateAccountAnswer' => 'To create an account using a web browser:'.
								'<ol>'.
								'<li>Go to <a href=\''.config('app.url').'\'>'.config('app.url').'</a>.</li>'.
								'<li>Click on the register link at the top of the page.</li>'.
								'<li>Enter your name, email address, username and password.</li>'.
								(config_cache('pixelfed.enforce_email_verification') != true) ?
									'<li>Wait for an account verification email, it may take a few minutes.</li>'
								: ''.
								'</ol>',

	'howUpdateProfileAsk' => 'How to I update profile info like name, bio, email?',
	'howUpdateProfileAnswer' => 'You can update your account by visiting the <a href=\''.route('settings').'\'>account settings</a> page.',

	'howInactiveUserAsk' => 'What can I do if a username I want is taken but seems inactive?',
	'howInactiveUserAnswer' => 'If your desired username is taken you can add underscores, dashes, or numbers to make it unique.',

	'whyChantUserAsk' => 'Why can\'t I change my username?',
	'whyChantUserAnswer' => 'Pixelfed is a federated application, changing your username is not supported in every <a href=\'https://en.wikipedia.org/wiki/ActivityPub\'>federated software</a> so we cannot allow username changes. Your best option is to create a new account with your desired username.',

	'whyReceiveEmaillAsk' =>	'I received an email that I created an account, but I never signed up for one.',
	'whyReceiveEmaillAnswer' =>	'Someone may have registered your email by mistake. If you would like your email to be removed from the account please contact an admin of this instance.',

	'whyExistsEmailAsk' => 'I can\'t create a new account because an account with this email already exists.',
	'whyExistsEmailAnswer' => 'You might have registered before, or someone may have used your email by mistake. Please contact an admin of this instance.',

	'hashtagLead' => 'A hashtag — written with a # symbol — is used to index keywords or topics.',
	'hashtagInfo' => '<p class="font-weight-bold h5 pb-3">Using hashtags to categorize posts by keyword</p>'.
		'<ul>'.
		'<li class="mb-3 ">People use the hashtag symbol (#) before a relevant phrase or keyword in their post to categorize those posts and make them more discoverable.</li>'.
		'<li class="mb-3 ">Any hashtags will be linked to a hashtag page with other posts containing the same hashtag.</li>'.
		'<li class="mb-3">Hashtags can be used anywhere in a post.</li>'.
		'<li class="">You can add up to 30 hashtags to your post or comment.</li>'.
		'</ul>',

	'howUseHashtagAsk' => 'How do I use a hashtag on Pixelfed?',
	'howUseHashtagAnswer' => '<ul>'.
							'<li>You can add hashtags to post captions, if the post is public the hashtag will be discoverable.</li>'.
							'<li>You can follow hashtags on Pixelfed to stay connected with interests you care about.</li>'.
							'</ul>',

	'howFollowHashtagAsk' => 'How do I follow a hashtag?',
	'howFollowHashtagAnswer' => '<p>You can follow hashtags on Pixelfed to stay connected with interests you care about.</p>'.
								'<p class=\'mb-0\'>To follow a hashtag:</p>'.
								'<ol>'.
									'<li>Tap any hashtag (example: #art) you see on Pixelfed.</li>'.
									'<li>Tap <span class=\'font-weight-bold\'>Follow</span>. Once you follow a hashtag, you\'ll see its photos and videos appear in feed.</li>'.
								'</ol>'.
								'<p>To unfollow a hashtag, tap the hashtag and then tap Unfollow to confirm.</p>'.
								'<p class=\'mb-0\'>'.
									'You can follow up to 20 hashtags per hour or 100 per day.'.
								'</p>',

	'hashtagTipsTitle' => 'Hashtag Tips',

	'hashtagTips' => '<ul class=\'pt-3\'>'.
					'<li class=\'lead  mb-4\'>You cannot add spaces or punctuation in a hashtag, or it will not work properly.</li>'.
					'<li class=\'lead  mb-4\'>Any public posts that contain a hashtag may be included in search results or discover pages.</li>'.
					'<li class=\'lead \'>You can search hashtags by typing in a hashtag into the search bar.</li>'.
					'</ul>'
];
