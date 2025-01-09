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

,
    'sharingMediaTitle' => 'Sharing Photos & Videos',
    'howCreatePostAask' => 'How do I create a post?',
    'howCreatePostAnswer' => '<div>
				To create a post using a desktop web browser:
				<ol>
					<li>Go to <a href="'.config('app.url').'">'.config('app.url').'</a>.</li>
					<li>Click on the <i class="fas fa-camera-retro text-primary"></i> link at the top of the page.</li>
					<li>Upload your photo(s) or video(s), add an optional caption and set other options.</li>
					<li>Click on the <span class="font-weight-bold">Create Post</span> button.</li>
				</ol>
			</div>
			<div class="pt-3">
				To create a post using a mobile web browser:
				<ol>
					<li>Go to <a href="'.config('app.url').'">'.config('app.url').'</a>.</li>
					<li>Click on the <i class="far fa-plus-square fa-lg"></i> button at the bottom of the page.</li>
					<li>Upload your photo(s) or video(s), add an optional caption and set other options.</li>
					<li>Click on the <span class="font-weight-bold">Create Post</span> button.</li>
				</ol>
			</div>',
    'howAddMultiplePhotosAsk' => 'How do I share a post with multiple photos or videos?',
    'howAddMultiplePhotosAnswer' => 'During the compose process, you can select multiple files at a single time, or add each photo/video individually.',
    'howCaptionBeforeSharePhotoAsk' => 'How do I add a caption before sharing my photos or videos on Pixelfed?',
    'howCaptionBeforeSharePhotoAnswer' => '<div>
				During the compose process, you will see the <span class="font-weight-bold">Caption</span> input. Captions are optional and limited to <span class="font-weight-bold">'.config_cache('pixelfed.max_caption_length').'</span> characters.
			</div>',

    'howAddFilterAsk' => 'How do I add a filter to my photos?',
    'howAddFilterAnswer' => '<div>
				<p class="text-center">
					<span class="alert alert-warning py-2 font-weight-bold">This is an experimental feature, filters are not federated yet!</span>
				</p>
				To add a filter to media during the compose post process:
				<ol>
					<li>
						Click the <span class="btn btn-sm btn-outline-primary py-0">Options <i class="fas fa-chevron-down fa-sm"></i></span> button if media preview is not displayed.
					</li>
					<li>Select a filter from the <span class="font-weight-bold small text-muted">Select Filter</span> dropdown.</li>
				</ol>
			</div>',

    'howAddDescriptionPhotoAsk' => 'How do I add a description to each photo or video for the visually impaired?',
    'howAddDescriptionPhotoAnswer' => '<div>
				<p class="text-center">
					<span class="alert alert-warning py-2 font-weight-bold">This is an experimental feature!</span>
				</p>
				<p>
					You need to use the experimental compose UI found <a href="/i/compose">here</a>.
				</p>
				<ol>
					<li>Add media by clicking the <span class="btn btn-outline-secondary btn-sm py-0">Add Photo/Video</span> button.</li>
					<li>Set a image description by clicking the <span class="btn btn-outline-secondary btn-sm py-0">Media Description</span> button.</li>
				</ol>
				<p class="small text-muted"><i class="fas fa-info-circle mr-1"></i> Image descriptions are federated to instances where supported.</p>
			</div>',

    'howMediaTypesCanUploadAsk' => 'What types of photos or videos can I upload?',
    'howMediaTypesCanUploadAnswer' => 'You can upload the following media types:',
    'howDisablecommentsAsk' => 'How can I disable comments/replies on my post?',
    'howDisablecommentsAnswer' => '<div>
				To enable or disable comments/replies using a desktop or mobile browser:
				<ul>
					<li>Open the menu, click the <i class="fas fa-ellipsis-v text-muted mx-2 cursor-pointer"></i> button</li>
					<li>Click on <span class="small font-weight-bold cursor-pointer">Enable Comments</span> or <span class="small font-weight-bold cursor-pointer">Disable Comments</span></li>
				</ul>
			</div>',
    'howManyTagMentionAsk' => 'How many people can I tag or mention in my comments or posts?',
    'howManyTagMentionAnswer' => 'You can tag or mention up to 5 profiles per comment or post.',
    'whatArchiveMeanAsk'=> 'What does archive mean?',
    'whatArchiveMeanAnswer' => '<div>
				You can archive your posts which prevents anyone from interacting or viewing it.
				<br />
				<strong class="text-danger">Archived posts cannot be deleted or otherwise interacted with. You may not recieve interactions (comments, likes, shares) from other servers while a post is archived.</strong>
				<br />
			</div>',

    'howArchivePostAsk' => 'How can I archive my posts?',
    'howArchivePostAnswer' => '<div>
				To archive your posts:
				<ul>
					<li>Navigate to the post</li>
					<li>Open the menu, click the <i class="fas fa-ellipsis-v text-muted mx-2 cursor-pointer"></i> or <i class="fas fa-ellipsis-h text-muted mx-2 cursor-pointer"></i> button</li>
					<li>Click on <span class="small font-weight-bold cursor-pointer">Archive</span></li>
				</ul>
			</div>',
    'howUnarchivePostAsk'=>'How do I unarchive my posts?',
    'howUnarchivePostAnswer' => '<div>
				To unarchive your posts:
				<ul>
					<li>Navigate to your profile</li>
					<li>Click on the <strong>ARCHIVES</strong> tab</li>
					<li>Scroll to the post you want to unarchive</li>
					<li>Open the menu, click the <i class="fas fa-ellipsis-v text-muted mx-2 cursor-pointer"></i> or <i class="fas fa-ellipsis-h text-muted mx-2 cursor-pointer"></i> button</li>
					<li>Click on <span class="small font-weight-bold cursor-pointer">Unarchive</span></li>
				</ul>
			</div>',

    'discorverTitle' => 'Discover',
    'discoversubTitle' => 'Discover new posts, people and topics.',
    'howUseDiscover' => '<p class="font-weight-bold h5 pb-3">How to use Discover</p>
        <ul>
        <li class="mb-3 ">Click the <i class="far fa-compass fa-sm"></i> icon.</li>
        <li class="mb-3 ">View the latest posts.</li>
        </ul>',
    'discoverCategories' => '<p class="font-weight-bold h5 pb-3">Discover Categories <span class="badge badge-success">NEW</span></p>
        <p>Discover Categories are a new feature that may not be supported on every Pixelfed instance.</p>
        <ul>
        <li class="mb-3 ">Click the <i class="far fa-compass fa-sm"></i> icon.</li>
        <li class="mb-3 ">On the discover page, you will see a list of Category cards that links to each Discover Category.</li>
        </ul>',

    'discoverTips' => '<div class="card-header text-light font-weight-bold h4 p-4 bg-primary">Discover Tips</div>
    <div class="card-body bg-white p-3">
      <ul class="pt-3">
        <li class="lead  mb-4">To make your posts more discoverable, add hashtags to your posts.</li>
        <li class="lead  mb-4">Any public posts that contain a hashtag may be included in discover pages.</li>

      </ul>
    </div>',

    'dmSubTitle'=> 'Send and recieve direct messages from other profiles.',
    'howUseDirectMessagesAsk' => 'How do I use Pixelfed Direct?',
    'howUseDirectMessagesAnswer' => ' <div>
        <p>Pixelfed Direct lets you send messages to another account. You can send the following things as a message on Pixelfed Direct:</p>
        <ul>
            <li>
            Photos or videos you take or upload from your library
            </li>
            <li>
            Posts you see in feed
            </li>
            <li>
            Profiles
            </li>
            <li>
            Text
            </li>
            <li>
            Hashtags
            </li>
            <li>
            Locations
            </li>
        </ul>
        <p>To see messages you\'ve sent with Pixelfed Direct, tap <i class="far fa-comment-dots"></i> in the top right of feed. From there, you can manage the messages you\'ve sent and received.</p>
        <p>Photos or videos sent with Pixelfed Direct can\'t be shared through Pixelfed to other sites like Mastodon or Twitter, and won\'t appear on hashtag and location pages.</p>
        </div>',

    'howUnsedDirectMessageAsk' => 'How do I unsend a message I\'ve sent using Pixelfed Direct?',
    'howUnsedDirectMessageAnswer' => 'You can click the message and select the <strong>Delete</strong> option.',
    'canSendDirectMessageAsk' => 'Can I use Pixelfed Direct to send messages to people I’m not following?',
    'canSendDirectMessageAnswer' => 'You can send a message to someone you are not following though it may be sent to their filtered inbox and not easily seen.',
    'howReportDirectMessageAsk' => 'How do I report content that I\'ve recieved in a Pixelfed Direct message?',
    'howReportDirectMessageAnswer' => ' You can click the message and then select the <strong>Report</strong> option and follow the instructions on the Report page.',

    'timelineSubTitle' => 'Timelines are chronological feeds of posts.',
    'timelineHome' => 'Timeline with content from accounts you follow',
    'timelinePublic' => 'Timeline with content from other users on this server',
    'timelineNetwork' => 'Timeline with unmoderated content from other servers',
    'timelineTips' => 'Timeline Tips',
    'timelineTipsContent' => '<ul class="pt-3">
				<li class="lead mb-4">You can mute or block accounts to prevent them from appearing in home and public timelines.</li>
				<li class="lead mb-4">You can create <span class="font-weight-bold">Unlisted</span> posts that don\'t appear in public timelines.</li>
			</ul>',

    'safetyTipsSubTitle' =>'We are committed to building a fun, easy to use photo sharing platform that is safe and secure for everyone.',
    'safetyTipsKnowRules' => 'Know the rules',
    'safetyTipsKnowRulesContent' => 'To keep yourself safe, it is important to know the <a href="'.route('site.terms').'">terms of service</a> rules.',
    'safetyTipsAage' => 'Know the age guideline',
    'safetyTipsAageContent' => 'Please keep in mind that Pixelfed is meant for people over the age of 16 or 13 depending on where you live',
    'safetyTipsRport'=> 'Report problematic content',
    'safetyTipsRportContent' => 'You can report content that you think is in violation of our policies.',
    'safetyTipsVisility' => 'Understanding content visibility',
    'safetyTipsVisilityContent' => 'You can limit the visibility of your content to specific people, followers, public and more.',
    'safetyTipsPostsPrivacy' => 'Make your account or posts private',
    'safetyTipsPostsPrivacyContent' => 'You can make your account private and vet new follow requests to control who your posts are shared with.',
];
