<?php

return [

'remove.permanent.title' => 'Delete Your Account',
'remove.permanent.body' => '<p>Hi <span class="font-weight-bold">'.Auth::user()->username.'</span>,</p>

  	<p>We\'re sorry to hear you\'d like to delete your account.</p>

  	<p class="pb-1">If you\'re just looking to take a break, you can always <a href="'.route('settings.remove.temporary').'">temporarily disable</a> your account instead.</p>

    <p class="">When you press the button below, your photos, comments, likes, friendships and all other data will be removed permanently and will not be recoverable. If you decide to create another Pixelfed account in the future, you cannot sign up with the same username again on this instance.</p>

    <div class="alert alert-danger my-5">
      <span class="font-weight-bold">Warning:</span> Some remote servers may contain your public data (statuses, avatars, etc) and will not be deleted until federation support is launched.
    </div>',

'remove.permanent.confirm_check' => 'I confirm that this action is not reversible, and will result in the permanent deletion of my account.',
'remove.permanent.confirm_button' => 'Permanently delete my account',

'remove.temporary.title' => 'Temporarily Disable Your Account',
'remove.temporary.body' => '<p>Hi <span class="font-weight-bold">'.Auth::user()->username.'</span>,</p>

  	<p>You can disable your account instead of deleting it. This means your account will be hidden until you reactivate it by logging back in.</p>

  	<p class="pb-1">You can only disable your account once a week.</p>

  	<p class="font-weight-bold">Keeping Your Data Safe</p>
  	<p class="pb-3">Nothing is more important to us than the safety and security of this community. People put their trust in us by sharing moments of their lives on Pixelfed. So we will never make any compromises when it comes to safeguarding your data.</p>

  	<p class="pb-2">When you press the button below, your photos, comments and likes will be hidden until you reactivate your account by logging back in.</p>
',

'remove.temporary.button' => 'Temporarily Disable Account',
];
