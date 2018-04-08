<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Devil Time | by Pharosic</title>

    <link rel="shortcut icon" type="image/png" href="">

    <script src="<?php echo base_url('assets/js/vuejs/2.5.16/dist/vue'); ?><?php echo (ENVIRONMENT === 'development') ? '.js' : '.min.js'; ?>"></script>

    <link rel="stylesheet" href="<?php echo base_url('assets/css/semantic-ui/2.3.0/semantic.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/app.css'); ?>">

    <link rel="stylesheet" href="<?php echo base_url('assets/css/offline-js/0.7.19/themes/offline-language-english.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo base_url('assets/css/offline-js/0.7.19/themes/offline-theme-slide.min.css'); ?>">
  </head>
  <body>
    <div id="app" >
      <div class="ui two column doubling stackable grid">
        <div class="row" style="padding-bottom: 0px">

          <div class="side content five wide column">
            <div class="header">
              <div class="logo">
                <img class="ui mini image" src="http://ct.pharosic.com/assets/favicon-9ib6rx9h.png" alt="Devil Time">
              </div>
              <div class="links">
                <div class="ui transparent icon input">
                  <input class="search input" type="text" placeholder="Search...">
                  <i class="search icon"></i>
                </div>
              </div>
            </div>

            <div class="identity select">
              <div class="ui circular icon button" @click="avatarChange(0)"><i class="chevron left icon"></i></div>
              <div class="loader" v-show="prevLoading">
                <clip-loader :loading="prevLoading" color="#1678C2" size="50px" margin="10px" radius="100%" style="padding: 30px 0px;"></clip-loader>
                Loading preview...
              </div>
              <transition name="fadeIn">
                <div class="preview" v-show=" ! prevLoading">
                  <img class="ui small image" :src="avatarSelected.filename" :alt="avatarSelected.label">
                  <div class="identity-name">
                    <span>{{ identityMood }}</span>
                    <span>{{ identityLabel }}</span>
                  </div>
                </div>
              </transition>
              <div class="ui circular icon button" @click="avatarChange(1)"><i class="chevron right icon"></i></div>
            </div>

            <div class="mood select">
              <label>I'm feeling...</label>
              <beat-loader :loading="moodLoading" color="#1678C2" size="10px" margin="2px" radius="100%" style="margin-left: 20px; margin-top: 5px;"></beat-loader>
              <select class="ui search selection tiny compact dropdown" @change="moodChange" v-if="! moodLoading" v-model="moodSelected">
                <option :value="mood.value" v-for="mood in moods">{{ mood.label }}</option>
              </select>
            </div>

            <div class="post create">
              <button class="ui primary button" @click="showPostModal" v-model="user.post_count">Create New Confession</button>
              <br><small v-html="devilToken" style="color: #FFFFFF"></small>
            </div>

            <div class="footer">
              <div class="copyright">
                The above avatar is made by <a href="http://www.freepik.com" title="Freepik">Freepik</a> from <a href="https://www.flaticon.com/" title="Flaticon">Flaticon</a>.
              </div>
              <div class="copyright">
                All rights reserved © 2018 Pharosic. &nbsp;&nbsp; About  | Privacy | Terms | Feedback
              </div>
            </div>
          </div>

          <div class="main content eleven wide column">
            <div class="ui two column doubling grid">
              <div class="row">
                <div class="fourteen wide column">
                  <div class="ui secondary pointing menu">
                    <a class="active item">Latest</a>
                    <a class="item">Hot</a>
                    <a class="item">Newest</a>

                    <div class="right menu">
                      <div class="item" v-show="modalLoading">
                        <beat-loader :loading="modalLoading" color="#1678C2" size="10px" margin="2px" radius="100%" style="margin-left: 20px; margin-bottom: 5px;"></beat-loader>
                      </div>
                      <div class="item" v-show=" ! modalLoading" v-if=" ! fbLoggedIn">
                        <span>Login to comment</span>
                        <a href="#" @click="fbLogIn"><i class="facebook big icon"></i></a>
                        <a href="#" v-show="false"><img class="ui mini image" src="http://ct.pharosic.com/assets/favicon-9ib6rx9h.png"></a>
                      </div>
                      <div class="item" v-show=" ! modalLoading" v-if="fbLoggedIn">
                        <span>Hi, {{ fbData.name }}</span>
                        <span><a href="#" @click="fbLogOut" style="font-size: 20px; color: #1678C2"><i class="sign out alternate icon" alt="Log out my Facebook"></i></a></span>
                      </div>
                    </div>
                  </div>
                  <div class="loader" v-show="postLoading">
                    <beat-loader :loading="postLoading" color="#1678C2" size="15px" margin="2px" radius="100%" style="padding: 15px 0px;"></beat-loader>
                    Loading content...
                  </div>
                  <div class="loader" v-show="postNoContent">
                    No content.
                  </div>
                  <div class="ui container" v-show=" ! postLoading">
                    <article class="ui fluid card" v-for="post in posts">
                      <div class="article content">
                        <span class="meta">
                          <img class="ui mini spaced image" :src="post.author_avatar">
                          <span class="author">{{ post.author_name }}</span>
                          <span class="post-id">
                            <a :href="post.id">#{{ post.id }}</a>
                          </span>
                          <span class="in" v-if="post.source !== null">from</span>
                          <span class="category" v-if="post.source !== null">
                            <a href="#">{{ post.source }}</a>
                          </span>
                        </span>
                        <span class="right floated meta">
                          <time :data-tooltip="post.createdOn">{{ timeAgo(post.created_on) }}</time>
                        </span>
                        <div class="description">
                          <i>
                            <span class="post-id" v-show="false">
                              <a :href="post.id">#{{ post.id }}</a>
                            </span>
                            <span class="quote" v-show="false">in reply to</span>
                            <span class="quote-id" v-show="false">
                              <a :href="post.quote_id">#</a>
                            </span>
                          </i>
                          <div class="text">{{ post.description }}</div>
                        </div>
                      </div>
                      <div class="action content">
                        <a class="like" @click="fbLoggedIn ? postAction('like', post.id) : actionRequiredFb()">
                          <i :class="{'heart like icon': true, active: userLikedPost.includes(post.id) && fbLoggedIn }"></i>
                            {{ post.likes }}
                            <span v-if="post.likes <= 1">
                              like
                            </span>
                            <span v-else>
                              likes
                            </span>
                        </a>
                        <span class="right floated">
                          <a class="comment">
                            <i class="comment icon"></i>
                            {{ post.comments }}
                            <span v-if="post.comments <= 1">
                              comment
                            </span>
                            <span v-else>
                              comments
                            </span>
                          </a>
                        </span>
                      </div>

                      <div class="extra content">
                        <div class="ui comments" v-if="postComments[post.id].length > 0">
                          <a @click="loadComment(post.id)">View more comments</a>

                          <div class="comment" v-for="comment in postComments[post.id]">
                            <a class="avatar">
                              <img :src="comment.author_avatar">
                            </a>
                            <div class="content">
                              <a class="author">{{ comment.author_name }}</a>
                              <div class="metadata">
                                <span class="date">
                                  <time>{{ timeAgo(comment.created_on) }}</time>
                                </span>
                              </div>
                              <div class="text">
                                {{ comment.description }}
                              </div>
                              <div class="actions">
                              </div>
                            </div>
                          </div>

                          <div class="loader" v-show="loadingMoreComment">
                            <beat-loader :loading="loadingMoreComment" color="#1678C2" size="10px" margin="2px" radius="100%" style="padding: 5px 0px;"></beat-loader>
                          </div>

                        </div>
                      </div>
                      <div class="extra content">
                        <div class="ui large transparent right icon fluid input">
                          <input type="text" placeholder="Add Comment..." v-model="c_description[post.id]">
                          <a @click="fbLoggedIn ? commentSubmit(post.id) : actionRequiredFb()"><i class="reply icon"></i></a>
                        </div>
                      </div>
                    </article>
                    <span v-observe-visibility="loadPost">aaaaaaaa</span>
                    <div class="loader" v-show="loadingMorePost">
                      <beat-loader :loading="loadingMorePost" color="#1678C2" size="15px" margin="2px" radius="100%" style="padding: 15px 0px;"></beat-loader>
                      Loading...
                    </div>
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>


      <div class="ui basic small modal" v-show="postModal">
        <div class="page-loader main" v-show="modalLoading" style="display: flex; flex-direction: column; justify-content: center; align-items: center; color: #FFFFFF">
          <clip-loader :loading="modalLoading" color="#1678C2" size="50px" margin="10px" radius="100%" style="padding: 30px 0px"></clip-loader>
          Loading editor...
        </div>
        <form @submit.prevent="postSubmit" v-if=" ! modalLoading">
          <div class="ui header" style="color: #FFFFFF; font-size: 15px; text-align: justify; padding: 10px 20px" v-show="false">
            Choose at least one category
              <span class="" style="float: right; cursor: pointer" @click="closePostModal"><i class="close icon"></i></span>
          </div>
          <div class="ui form" style="padding: 0px 20px" v-show="false">
            <div class="grouped field" style="background: #FFFFFF; border-radius: 4px; padding: 10px 30px">
              <div class="inline field" style="display: flex; flex-direction: row; justify-content: space-around; flex-wrap: wrap;">
                <div class="ui toggle checkbox" style="margin: 10px" v-for="checkbox in categories">
                  <input type="checkbox" :id="checkbox.value" :value="checkbox.value" v-model="category">
                  <label style="color: #1E70BF">{{ checkbox.label }}</label>
                </div>
              </div>
            </div>
          </div>
          <div class="ui header" style="color: #FFFFFF; font-size: 15px; text-align: justify; padding: 5px 20px; margin-top: 20px">
            <img style="padding: 0px; margin: 0px; margin-right: 15px" src="https://image.flaticon.com/icons/png/512/301/301914.png">
            <span style="font-size: 20px">Stay evil and share your secret with us.</span>
            <span class="" style="padding: 10px; float: right; cursor: pointer" @click="closePostModal"><i class="close icon"></i></span>
          </div>
          <div class="ui form" style="padding: 0px 20px">
            <div class="field">
              <textarea style="font-size: 15px; background-color: #FAFBFC" rows="5" v-model="description" @focus="clearNotice();" @blur="showNotice();">{{ description }}</textarea>
            </div>
          </div>
          <div class="actions" style="display: flex; justify-content: space-between; padding: 15px">
            <div class="ui form" style="display: inline-block; margin-left: 5px">
              <div class="two wide field">
                <select class="ui search dropdown" v-model="source" style="width: 200px; font-size: 15px">
                  <option value="">Select University</option>
                  <option v-for="source in sources" :value="source.label">{{ source.label }}</option>
                </select>
              </div>
            </div>
            <button type="submit" class="ui primary ok button">
              <i class="paper plane icon"></i>
              Submit
            </button>
          </div>
        </form>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.1.1.min.js" integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8=" crossorigin="anonymous"></script>
    <script src="<?php echo base_url('assets/css/semantic-ui/2.3.0/semantic.min.js'); ?>"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/offline-js/0.7.19/offline.min.js"></script>
    <script src="https://cdn.bootcss.com/timeago.js/3.0.2/timeago.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/fingerprintjs2/1.6.1/fingerprint2.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.18.0/axios.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue-resource@1.5.0"></script>
    <script src="http://greyby.github.io/vue-spinner/dist/vue-spinner.js"></script>
    <script src="https://unpkg.com/vue-infinite-scroll"></script>
    <script src="<?php echo base_url('assets/js/vue-observe-visibility/dist/vue-observe-visibility.min.js'); ?>"></script>

    <script>

          Vue.http.options.emulateJSON = true;

          var BeatLoader = VueSpinner.BeatLoader;
          var ClipLoader = VueSpinner.ClipLoader;

          var textareaNotice = "Have an interesting story to share or just need to get something off your chest? Tell us your story here! No one will know it was you. Please be reminded to be socially responsible. No racial, religious or other forms of sensitive material. These entries will be rejected (and make us sad).";

          var app = new Vue({
            el: "#app",
            data: {
              //----- Global -----//
                user: [],
                devilToken: '',
                devilTokenCount: 0,

                fbLoggedIn: false,
                fbData: '',

              //----- Side Content -----//
                prevLoading: true,
                moodLoading: true,
                modalLoading: true,

                test: '',
                moods: [],
                avatars: [],

                identityMood: '',
                moodSelected: '',

                identityLabel: '',
                avatarSelected: [],

                avatarIndex: 0,

              //----- Main Content -----//
                postLoading: true,
                postNoContent: false,

                posts: [],
                totalEntries: 0,

                loadPostOffset: 0,
                loadPostLimit: 20,
                loadingMorePost: true,

                loadCommentOffset: 0,
                loadCommentLimit: 3,
                totalComments: [],
                postComments: [],
                loadingMoreComment: false,
                commentLeftOver: 0,

                userLikedPost: [],
                userSharedPost: [],

                c_description: [],

              //----- Post Modal -----//
                postModal: false,

                categories: [],
                sources: [],
                category: [],

                description: textareaNotice,
                source: '',

            },
            beforeCreate: function () {
              //----- Global -----//
                var self = this;

                setTimeout(function() {
                  new Fingerprint2().get(function(hash, components) {
                    // Fetch user.
                    self.$http.get('<?php echo site_url(); ?>/api/user/GET/hash/' + hash)
                      .then(function(response){

                        // Fast track, if User exist, fetch user data first.
                        if( response.data.body !== '') {
                          self.user = response.data.body
                          self.modalLoading = false

                          if( self.user.post_count >= 3 ) {
                            self.devilToken = 'You have no more devil token.';
                          } else if( self.user.post_count == 2 ) {
                            self.devilToken = 'You have 1 more devil token.';
                          } else {
                            self.devilToken = 'You have ' + (3 - self.user.post_count) + ' devil tokens.';
                          }

                          self.devilTokenCount = self.user.post_count;
                        }

                        // Check user footprint.
                        self.$http.post('<?php echo site_url(); ?>/api/user/POST/', {
                          bfp_hash: hash,
                          bfp_components: components
                        })
                          .then(function(response){
                            self.user = response.data.body
                            self.modalLoading = false

                            if( self.user.post_count >= 3 ) {
                              self.devilToken = 'You have no more devil token.';
                            } else if( self.user.post_count == 2 ) {
                              self.devilToken = 'You have 1 more devil token.';
                            } else {
                              self.devilToken = 'You have ' + (3 - self.user.post_count) + ' devil tokens.';
                            }

                            self.devilTokenCount = self.user.post_count;

                            console.log('GET UUID: Succeed!');
                          })
                          .catch(function(error) {
                            console.log('GET UUID Error: ' + error)
                          });

                      })
                      .catch(function(error){
                        console.log('GET UIID Error: ' + error)
                      });


                  })
                }, 3000)

                window.fbAsyncInit = function() {
                  FB.init({
                    appId      : '403552113156005',
                    cookie     : true,
                    xfbml      : true,
                    version    : 'v2.8'
                  });

                  FB.AppEvents.logPageView();

                  FB.getLoginStatus(function(response) {
                    if (response.status === 'connected') {
                      // the user is logged in and has authenticated your
                      // app, and response.authResponse supplies
                      // the user's ID, a valid access token, a signed
                      // request, and the time the access token
                      // and signed request each expire
                      var uid = response.authResponse.userID;
                      var accessToken = response.authResponse.accessToken;

                      FB.api('/me', {fields: 'id, email, cover, name, first_name, last_name, age_range, link, gender, locale, picture, timezone'}, function(response) {

                        self.fbData = response;

                        self.$http.post('<?php echo site_url(); ?>/api/user/GET/fb_connect/' + self.fbData.id)
                          .then(function(response){
                            console.log('FB Connect: Succeed!');
                            self.fbLoggedIn = true;
                          })
                          .catch(function(error){
                             console.error('FB Connect Error: ' + error);
                          });


                        self.$http.get('<?php echo site_url(); ?>/api/action/GET/all/' + self.fbData.id)
                          .then(function(response) {

                            for (let i = 0; i < response.body.body.liked.length; i++) {
                              if( ! self.userLikedPost.includes(response.body.body.liked[i]) ) {
                                self.userLikedPost.push(response.body.body.liked[i]);
                              }
                            }

                            //self.userSharedPost.push(response.shared);
                          })
                          .catch(function(error) {
                            console.error('GET Post Actions Error: ' + error);
                          });

                      });


                      /*self.$http.post('<?php echo site_url(); ?>/api/user/GET/', {
                        bfp_hash: hash,
                        bfp_components: components
                      })
                        .then(function(response){ */

                    } else if (response.status === 'not_authorized') {
                      // the user is logged in to Facebook,
                      // but has not authenticated your app
                      console.log('FB Connect Error: User has not authorized access.');
                    } else {
                      // the user isn't logged in to Facebook.
                      console.log('FB Connect Error: User is not logged in.');

                    }
                  });
                };

                (function(d, s, id){
                   var js, fjs = d.getElementsByTagName(s)[0];
                   if (d.getElementById(id)) {return;}
                   js = d.createElement(s); js.id = id;
                   js.src = "https://connect.facebook.net/en_US/sdk.js";
                   fjs.parentNode.insertBefore(js, fjs);
                 }(document, 'script', 'facebook-jssdk'));
            },
            mounted: function () {

              //----- Global -----//
                var self = this

              //----- Side Content -----//
                var getMoods = axios.get('<?php echo site_url(); ?>/api/option/GET/mood')
                  .then(function(result){
                    self.moods = result.data.body;
                    self.moodLoading = false;

                    var moodRandom = Math.floor( Math.random() * result.data.body.length );
                    self.identityMood = result.data.body[moodRandom].value;
                    self.moodSelected = result.data.body[moodRandom].value;

                    console.log('GET Moods: Succeed!');
                  })
                  .catch(function(error){
                    console.error('GET Moods Error: ' + error);
                  });

                var getAvatars = axios.get('<?php echo site_url(); ?>/api/option/GET/avatar')
                  .then(function(result){
                    self.avatars = result.data.body;

                    var avatarRandom = Math.floor( Math.random() * result.data.body.length );
                    self.identityLabel = result.data.body[avatarRandom].label;
                    self.avatarSelected = result.data.body[avatarRandom];


                    self.avatarIndex= avatarRandom;

                    self.prevLoading = false;

                    console.log('GET Avatars: Succeed!');
                  })
                  .catch(function(error){
                    console.error('GET Avatars Error: ' + error);
                  });

              //----- Main Content -----//

              //----- Post Modal -----//
              /*  var getCategories = axios.get('/dt/index.php/home/get_options/category')
                  .then(function(result){
                    self.categories = result.data;
                  })
                  .catch(function(error){
                    console.error(error);
                  });
              */

                var getSources = axios.get('<?php echo site_url(); ?>/api/option/GET/source')
                  .then(function(result){
                    self.sources = result.data.body;
                    console.log('GET Sources: Succeed!')
                  })
                  .catch(function(error){
                    console.error('GET Sources Error: ' + error);
                  });
            },
            methods: {
                commentSubmit: function (postID) {

                  if( ! this.c_description[postID] || this.c_description[postID] === '') {
                    alert('Please enter your comment.');
                  } else {
                    this.$http.post('<?php echo site_url(); ?>/api/comment/POST/', {
                      author_id: this.fbData.id,
                      author_name: this.fbData.name,
                      author_avatar: this.fbData.picture.data.url,
                      description: this.c_description[postID],
                      post_id: postID,
                    })
                      .then(function(response){
                        alert('Your comment on post #' + postID +' has been posted.')
                        console.log('Post Submit: Succeed!');
                        this.c_description[postID] = '';
                        console.log(this.postComments[postID]);

                        this.postAction('comment', postID);
                        this.loadComment(postID);

                        /* var newComment = '{id: "0", author_id: ' + this.fbData.id + ', author_name: ' + this.fbData.name + ', author_avatar: ' + this.fbData.picture.data.url + ', description: ' + this.c_description[postID] + '}';

                        if( ! this.postComments[postID] || this.postComments[postID] === '' ) {
                          this.postComments[postID] = [];
                        }

                        this.postComments[postID].push(newComment); */
                      })
                      .catch(function(error){
                          alert('Comment Submit: Unexpected error occurred. Please send feedback to admin.')
                          console.error('Comment Submit Error: ' + error);
                      });
                  }

                },

                loadComment: function (postID) {

                  this.loadingMoreComment = true;

                  if( ! this.totalComments[postID] || this.totalComments[postID] === '' ) {
                    var leftOver = 0;
                  } else {
                    var leftOver = this.totalComments[postID] - this.loadCommentOffset;
                  }

                  if( leftOver === 0) {
                    this.loadingMoreComment = false;
                    this.loadCommentOffset = this.loadCommentOffset + leftOver;
                  } else if ( leftOver <= this.loadCommentLimit ) {
                    this.loadCommentOffset = this.loadCommentOffset + leftOver;
                    this.loadingMoreComment = true;
                    // BUG: duplicate posts when loading more posts after new post submitted.
                  } else if( leftOver > this.loadCommentLimit ) {
                    this.loadCommentOffset = this.loadCommentOffset + this.loadCommentLimit;
                    this.loadingMoreComment = true;
                  }

                  var getComments = axios.get('<?php echo site_url(); ?>/api/comment/GET/' + postID + '/limit/' + this.loadCommentLimit + '/offset/' + this.loadCommentOffset)
                    .then( (result) => {
                      console.log('GET Comments: Succeed!');

                      if( ! this.postComments[postID] ) {
                        this.postComments[postID] = [];
                      }

                      for (var i = 0, len = result.data.body.length; i < len; i++) {
                        this.postComments[postID].push(result.data.body[i]);
                      }

                      if(this.postComments[postID].length === 0) {
                        //this.postNoContent = true;
                      } else {
                        //this.postNoContent = false;
                      }

                      this.totalComments[postID] = result.data.total_comments;
                      this.loadingMoreComment = false;
                    })
                    .catch( (error) => {
                      console.error('GET Comments Error: ' + error);
                    });

                },

              //----- FB Connect -----//
                fbLogIn: function () {

                  var self = this;

                  FB.login(function(response) {

                      if (response.authResponse) {
                       FB.api('/me', {fields: 'id, email, cover, name, first_name, last_name, age_range, link, gender, locale, picture, timezone'}, function(response) {

                         self.fbData = response;

                         self.$http.post('<?php echo site_url(); ?>/api/user/POST/fb_connect/', {
                           user_id: self.user.id,
                           fb_id: self.fbData.id,
                           email: self.fbData.email,
                           full_name: self.fbData.name,
                           first_name: self.fbData.first_name,
                           last_name: self.fbData.last_name,
                           age: self.fbData.age_range.min,
                           gender: self.fbData.gender,
                           profile: self.fbData.link,
                           profile_avatar: self.fbData.picture.data.url,
                           profile_cover: self.fbData.cover.source,
                           locale: self.fbData.locale,
                           timezone: self.fbData.timezone
                         })
                           .then(function(response){
                             console.log('FB Connect: Succeed!');
                             alert('Welcome back, ' + self.fbData.name + '!\n\nNote: Please log out Facebook before posting your new confession in order to stay anonymous.')
                             self.fbLoggedIn = true;

                             self.$http.get('<?php echo site_url(); ?>/api/action/GET/all/' + self.fbData.id)
                               .then(function(response) {

                                 for (let i = 0; i < response.body.body.liked.length; i++) {
                                   if( ! self.userLikedPost.includes(response.body.body.liked[i]) ) {
                                     self.userLikedPost.push(response.body.body.liked[i]);
                                   }
                                 }

                                 //self.userSharedPost.push(response.shared);
                               })
                               .catch(function(error) {
                                 console.error('GET Post Actions Error: ' + error);
                               });
                           })
                           .catch(function(error){
                              console.error('FB Connect Error: ' + error);
                           });
                       });
                      } else {
                       console.error('FB Connect Error: User cancelled login or did not fully authorize.');
                      }

                  },{
                    scope: 'public_profile, email',
                    return_scopes: true,
                    auth_type: 'rerequest'
                  });
                },

                fbLogOut: function () {
                  var self = this;
                  FB.logout(function(response) {
                    console.log('FB Connect: User logged out!');
                    alert('You have successfully logged out and diving among the anonymous devils.');
                    self.fbLoggedIn = false;
                    self.fbData = '';
                  });
                },

              //----- Side Content -----//
                moodChange: function () {
                  this.identityMood = this.moodSelected;
                },

                avatarChange: function (action) {
                  this.prevLoading = true;

                  var arrLen = this.avatars.length;
                  var maxArrIndex = arrLen - 1;
                  // this.avatarIndex= Math.floor(Math.random() * arrLen);

                  if(action == 0){
                    if(this.avatarIndex == 0){
                      this.avatarIndex = maxArrIndex;
                    } else {
                      this.avatarIndex = this.avatarIndex - 1;
                    }
                  } else if (action == 1) {
                    if(this.avatarIndex == maxArrIndex){
                      this.avatarIndex = 0;
                    } else {
                      this.avatarIndex = this.avatarIndex + 1;
                    }
                  }

                  this.avatarSelected = this.avatars[this.avatarIndex];
                  this.identityLabel =  this.avatars[this.avatarIndex].label;

                  this.prevLoading = false;
              },

              //----- Main Content -----//
                timeAgo: function (dateTime) {

                  return timeago().format(dateTime);
                },

                actionRequiredFb: function () {
                  alert('You need to connect with Facebook in order to perform the following action.');
                },

                postAction: function(action, postID) {
                  if( this.fbLoggedIn && action === 'like' ) {
                    if( this.userLikedPost.includes(postID) ) {
                      // Communicate with server to record action.
                      this.$http.post('<?php echo site_url(); ?>/api/action/POST/like', {
                        post_id: postID,
                        fb_id: this.fbData.id,
                      })
                        .then(function(response) {
                          // Remove post id from liked list.
                          this.userLikedPost.splice(this.userLikedPost.indexOf(postID), 1);

                          // Remove like.
                          this.posts.forEach((each, i) => {
                            if (each.id == postID) {
                              this.posts[i].likes = Number(this.posts[i].likes) - 1;
                            }
                          });
                        })
                        .catch(function(error) {
                          console.error('Post Unlike Error: ' + error);
                        });

                    } else {

                      // Communicate with server to record action.
                      this.$http.post('<?php echo site_url(); ?>/api/action/POST/like', {
                        post_id: postID,
                        fb_id: this.fbData.id,
                      })
                        .then(function(response) {
                          // Add post id into liked list.
                          this.userLikedPost.push(postID);

                          // Add like.
                          this.posts.forEach((each, i) => {
                            if (each.id == postID) {
                              this.posts[i].likes = Number(this.posts[i].likes) + 1;
                            }
                          });
                        })
                        .catch(function(error) {
                          console.error('Post Like Error: ' + error);
                        });
                    }

                  } else if( this.fbLoggedIn && action === 'comment' ) {
                    this.posts.forEach((each, i) => {
                      if (each.id == postID) {
                        this.posts[i].comments = Number(this.posts[i].comments) + 1;
                      }
                    });
                  } else if( this.fbLoggedIn && action === 'share' ) {

                  }
                },

              //----- Post Modal -----//
                showPostModal: function () {
                  $('.ui.basic.modal').modal('setting', {autofocus: false}).modal('show'); // TODO: Emit semantic-ui jquery modal
                },
                closePostModal: function () {
                  $('.ui.basic.modal').modal('hide'); // TODO: Emit semantic-ui jquery modal
                },
                clearNotice: function () {
                  if( this.description === textareaNotice ) {
                    this.description = '';
                  }
                },
                showNotice: function () {
                  if( this.description === '' ) {
                    this.description = textareaNotice;
                  }
                },
                postSubmit: function (event) {

                  this.errors = [];

                  if( this.category.length <= 0 ) {
                    // NOTE (DEPRECIATED): this.errors.push('Please select at least one category.');
                  }
                  if( ! this.description || this.description === textareaNotice) {
                    this.errors.push('Post description should not be empty.');
                  } else if( this.description.length < 15 ) {
                    this.errors.push('Please describe your confession further.');
                  }
                  if( this.devilTokenCount >= 3) {
                    this.errors.push('You have exceeded daily post limit = 3. Please come back tomorrow.');
                  }

                  if( this.errors.length > 0) {
                    alert(this.errors.join(' '));
                  } else {
                    this.$http.post('<?php echo site_url(); ?>/api/post/POST/', {
                      author_name: this.identityMood + ' ' + this.identityLabel,
                      author_avatar: this.avatarSelected.filename,
                      description: this.description,
                      source: this.source,
                      // TODO: quote_id: this.quote_id,
                      user_id: this.user.id,
                    })
                      .then(function(response){
                        alert('Your confession has been posted.')
                        console.log('Post Submit: Succeed!');
                        event.target.reset();
                        this.description = '';
                        this.source = '';
                        this.category = [];

                        if( this.devilTokenCount <= 3 ) {
                          this.devilTokenCount = ++this.devilTokenCount;
                        }

                        if( this.devilTokenCount >= 3 ) {
                          this.devilToken = 'You have no more devil token.';
                        } else if( this.devilTokenCount == 2 ) {
                          this.devilToken = 'You have 1 more devil token.';
                        } else {
                          this.devilToken = 'You have ' + (3 - this.devilTokenCount) + ' devil tokens.';
                        }

                      })
                      .catch(function(error){
                          alert('Post Submit: Unexpected error occurred. Please send feedback to admin.')
                          console.error('Post Submit Error: ' + error);
                      });
                  }
                },
                loadPost: function () {

                  this.loadingMorePost = false;

                  var leftOver = this.totalEntries - this.loadPostOffset;

                  if( leftOver === 0) {
                    this.loadingMorePost = false;
                    this.loadPostOffset = this.loadPostOffset + leftOver;
                  } else if ( leftOver <= this.loadPostLimit ) {
                    this.loadPostOffset = this.loadPostOffset + leftOver;
                    this.loadingMorePost = true;
                    // BUG: duplicate posts when loading more posts after new post submitted.
                  } else if( leftOver > this.loadPostLimit ) {
                    this.loadPostOffset = this.loadPostOffset + this.loadPostLimit;
                    this.loadingMorePost = true;
                  }

                  var getPosts = axios.get('<?php echo site_url(); ?>/api/post/GET/limit/' + this.loadPostLimit + '/offset/' + this.loadPostOffset)
                    .then( (result) => {
                      console.log('GET Posts: Succeed!');

                      for (var i = 0, len = result.data.body.length; i < len; i++) {
                        this.posts.push(result.data.body[i]);

                        this.loadComment(result.data.body[i].id);
                      }

                      if(this.posts.length === 0) {
                        this.postNoContent = true;
                      } else {
                        this.postNoContent = false;
                      }

                      this.totalEntries = result.data.total_entries;
                      this.postLoading = false;
                    })
                    .catch( (error) => {
                      console.error('GET Posts Error: ' + error);
                    });

                }
            },
            components: {
              BeatLoader,
              ClipLoader
            }
          })

          $('.ui.checkbox').checkbox();
          $('.ui.dropdown').dropdown();

          function storeUserSession() {
              var identityMood = $('#identity-mood').text();
              var identityLabel = $('#identity-label').text();

              var userInfo = [
                {
                  identity: identityMood + ' ' + identityLabel,
                }
              ];

              localStorage.setItem('dtUserInfo', JSON.stringify(userInfo));
          }

          storeUserSession();

          $('#identity-name').bind('DOMSubtreeModified', function() {
            storeUserSession();
          });
    </script>
  </body>
</html>
