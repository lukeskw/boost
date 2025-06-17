import axios from 'axios';
import moment from 'moment-timezone';
import '../css/app.css';

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

moment.tz.setDefault(window.AiAssistant.timezone);

window.AiAssistant.basePath = '/' + window.AiAssistant.path;
