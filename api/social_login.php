<?php
/* Google / Facebook sign-in is NOT enabled.
   The old demo accepted any name + email, which on a real server would let
   anyone sign in as someone else. Real sign-in needs a Google OAuth Client ID /
   Facebook App ID and server-side token verification – see README. */
require __DIR__ . '/_bootstrap.php';
fail('Google and Facebook sign-in are not set up yet. Please create an account with your email instead.', 501);
