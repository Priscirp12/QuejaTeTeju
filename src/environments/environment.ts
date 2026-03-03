// This file can be replaced during build by using the `fileReplacements` array.
// `ng build` replaces `environment.ts` with `environment.prod.ts`.
// The list of file replacements can be found in `angular.json`.

export const environment = {
  production: false,
  // during development we directly hit the WAMP backend. Adjust host/port if necessary.
  // you can also use the proxy setup (see proxy.conf.json) by setting this to '/api',
  // but if you have trouble with 404s you may prefer the explicit absolute URL below.
  apiUrl: 'http://localhost/PETICIONES/api'
};

/*
 * For easier debugging in development mode, you can import the following file
 * to ignore zone related error stack frames such as `zone.run`, `zoneDelegate.invokeTask`.
 *
 * This import should be commented out in production mode because it will have a negative impact
 * on performance if an error is thrown.
 */
// import 'zone.js/plugins/zone-error';  // Included with Angular CLI.
