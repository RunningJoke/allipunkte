


const RequestManager = function() {

}

RequestManager.prototype.install = function(Vue) 
{
    Vue.prototype.$request = this
}

RequestManager.prototype.sendJsonRequest = async function(targetUrl, method = "GET", body = null) {
	
	let requestConfiguration = {
		"method": method,
		"credentials": 'include',
		"headers": {
			'Accept' : 'application/json',
			'Content-Type' : 'application/json'
		}
	}
	if(body !== null && method !== 'GET') {
		//check if body is an object, then stringify
		let strBody = ""
		if(!!body && typeof body == 'object') {
			strBody = JSON.stringify(body)
		} else if(typeof body == 'string') {
			strBody = body
		} else {
			throw('invalid request body type: '+ typeof body)
		}
		requestConfiguration['body'] = strBody
	}

	let response = await fetch(targetUrl, requestConfiguration)
	if(!response.ok) {
		throw('error fetching data from '+targetUrl+' with method '+method)
	}
	let data = await response.json()
	return data
}


const requestManager = new RequestManager()

export { requestManager }