const STATUS_SUCCESS = 'success';
const STATUS_ERROR = 'error';
var app = new Vue({
	el: '#app',
	data: {
		login: '',
		pass: '',
		post: false,
		postComments: [],
		invalidLogin: false,
		invalidPass: false,
		invalidSum: false,
		posts: [],
		addSum: 0,
		amount: 0,
		likes: 0,
		reply_id: false,
		reply_to: false,
		user_history: [],
		booster_history: [],
		commentText: '',
		boosterpacks: [],
		totals: false,
	},
	computed: {
		test: function () {
			var data = [];
			return data;
		}
	},
	created(){
		var self = this
		axios
			.get('/main_page/get_all_posts')
			.then(function (response) {
				self.posts = response.data.posts;
			})

		axios
			.get('/main_page/get_boosterpacks')
			.then(function (response) {
				self.boosterpacks = response.data.boosterpacks;
			})
	},
	methods: {
		logout: function () {
			console.log ('logout');
		},
		logIn: function () {
			var self= this;
			if(self.login === ''){
				self.invalidLogin = true
			}
			else if(self.pass === ''){
				self.invalidLogin = false
				self.invalidPass = true
			}
			else{
				self.invalidLogin = false
				self.invalidPass = false

				form = new FormData();
				form.append("login", self.login);
				form.append("password", self.pass);

				axios.post('/main_page/login', form)
					.then(function (response) {
						if(response.data.user) {
							location.reload();
						}
						setTimeout(function () {
							$('#loginModal').modal('hide');
						}, 500);
					})
			}
		},
		addComment: function(id) {
			var self = this;
			if(self.commentText) {

				var comment = new FormData();
				comment.append('postId', id);
				comment.append('commentText', self.commentText);
				if (self.reply_id) {
					comment.append('replyId', self.reply_id);
				}

				axios.post(
					'/main_page/comment',
					comment
				).then(function (response) {
					self.postComments.push(response.data.comment);
					self.commentText = '';
					self.reply_to = false;
				});
			}

		},
		refill: function () {
			var self= this;
			if(self.addSum === 0){
				self.invalidSum = true
			}
			else{
				self.invalidSum = false
				sum = new FormData();
				sum.append('sum', self.addSum);
				axios.post('/main_page/add_money', sum)
					.then(function (response) {
						setTimeout(function () {
							$('#addModal').modal('hide');
						}, 500);
					})
			}
		},
		openPost: function (id) {
			var self= this;
			axios
				.get('/main_page/get_post/' + id)
				.then(function (response) {
					self.post = response.data.post;
					self.postComments = response.data.post.coments;
					if(self.post){
						setTimeout(function () {
							$('#postModal').modal('show');
						}, 500);
					}
				})
		},
		addLike: function (type, id) {
			var self = this;
			const url = '/main_page/like_' + type + '/' + id;
			axios
				.get(url)
				.then(function (response) {
					self.likes = response.data.likes;
				})

		},
		selectForReply: function (id, comment) {
			this.reply_id = id;
			this.reply_to = comment;
		},
		getHistory: function () {
			var self = this;
			const history_url = '/main_page/get_history';
			axios
				.get(history_url)
				.then(function (response) {
					self.user_history = response.data.history;
				});
			const totals_url = '/main_page/get_user_totals';
			axios
				.get(totals_url)
				.then(function (response) {
					self.totals = response.data.totals;
				});
			const boosterpack_history_url = '/main_page/get_boosterpack_history';
			axios
				.get(boosterpack_history_url)
				.then(function (response) {
					self.booster_history = response.data.booster_history
				});
		},
		buyPack: function (id) {
			var self= this;
			var pack = new FormData();
			pack.append('id', id);
			axios.post('/main_page/buy_boosterpack', pack)
				.then(function (response) {
					self.amount = response.data.amount
					if(self.amount !== 0){
						setTimeout(function () {
							$('#amountModal').modal('show');
						}, 500);
					}
				}).catch(function (error) {
					alert(error.response.data.error_message);
			})
		}
	}
});

