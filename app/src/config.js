import devConfig from './config.dev.json'
import prodConfig from './config.prod.json'

export default process.env.NODE_ENV === 'production' ? prodConfig : devConfig
