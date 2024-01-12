import { EventEmitter } from "eventemitter3";
import { highEndBlurryRecognition } from "..";
import { Barcode } from "./barcode";
import { BrowserHelper } from "./browserHelper";
import { DataCaptureLoader } from "./dataCaptureLoader";
import { dataCaptureWorkerBlob } from "./workers/dataCaptureWorker";
class BlurryRecognitionPreloaderEventEmitter extends EventEmitter {
}
export class BlurryRecognitionPreloader {
    static writableDataPath = "/scandit_sync_folder_preload";
    static fsObjectStoreName = "FILE_DATA";
    // From AndroidLowEnd
    static defaultBlurryTableFiles = [
        "/281f654b8ff82daa99ad885ef39a15fb.scandit",
        "/2c53cab9a0737960a56ec66ae2a1c2cd.scandit",
        "/6b5c52b06ec25af4ac80a807f08c8a22.scandit",
        "/a161ee7d1b0a5c1f3cf6fbdf41d544da.scandit",
        "/c22ac7d324d8076de6c6a20667cb58fb.scandit",
        "/db921bb2d0f06e25180139366579b318.scandit",
        "/53f7125006c6641b34eed19c3863e42a.scandit",
        "/6de91450426ad609398ffc0dd417066c.scandit",
        "/de892fb0f0b231aa877beb05ef628982.scandit",
        "/27efecb40cc701f1568a100081473470.scandit",
        "/37c247f983a341588eca92f4095982f6.scandit",
        "/9fa42646d1b7ab87f5dbc66c7423275e.scandit",
        "/423b33a061cea7c3e9a346761064e696.scandit",
        "/47fe40b164917635e99f9d917ea873df.scandit",
        "/c86520b1e03d20ad23c7aa3057bc00aa.scandit",
        "/8ca1870a78346f894973385bac861368.scandit",
        "/d6bc81e9953262efe2ba28dc88a255c7.scandit",
        "/f2cc6637d1f431587ae8f0050944b1f6.scandit",
        "/3a749978f5d673142bdcb360f7f6f943.scandit",
        "/5794f5949d313c1c3b8d0ad8235352a4.scandit",
        "/9a5f9ee72580f702ea388b0b2b29ad06.scandit", // msi-plessey
    ].map((path) => {
        return `${BlurryRecognitionPreloader.writableDataPath}${path}`;
    });
    // From AndroidGeneric
    static highEndBlurryTableFiles = [
        "/190321966be83d9d4eb3ebef42e0425c.scandit",
        "/51e855045b2f56ecc18e92b1c53c302c.scandit",
        "/5e2464c47c50ac324766b4f7836a9238.scandit",
        "/7f95c7a85f7644081420026f011afc26.scandit",
        "/9768cd567a0813ef9e2b35377e5763b3.scandit",
        "/9da839200be5f945ae07ce56be4b519b.scandit",
        "/1e09ddd31d6b791f2aff1fc178fc0fa6.scandit",
        "/4e6cfc8f10105c1c88be188781e1fd09.scandit",
        "/777cff34a643cc67783abc5a2cd28028.scandit",
        "/7a47da9075339736d97d20e74743adb4.scandit",
        "/83b2f2f20564df0c4c3343abdd33ce2c.scandit",
        "/876aa038cde59f3bc554408ef6de5aba.scandit",
        "/525eb9a51a6d7a247a718bd47e8e6fca.scandit",
        "/5c72db14fd540dd7ed0a1a8e03d1a08d.scandit",
        "/61014b41bd1a00c842a881267d5b47bf.scandit",
        "/748fd6c978b0f7e02fa4c5f481f69a92.scandit",
        "/7db7b21c46a607367ee9993279d4bf06.scandit",
        "/b5189294cd7b8c5428008b37a4ebee57.scandit",
        "/28307ba88850bdbf0ca3c02bc00ce76c.scandit",
        "/2f239cbc1915384192586bb52f1e20d5.scandit",
        "/443c732a519cd45ae3de1b90eca2221a.scandit",
        "/4a7685d7441e9ed9b08342273033d654.scandit",
        "/5d777eae7a2b98a13183dbab6ab05f87.scandit",
        "/bfdd27616e9e53ec1256e61025c87e4f.scandit",
        "/0cf46df76c8afda2dd17eada4c0aa3d9.scandit",
        "/55c134f1aa08ae47b6f1101b03ff1369.scandit",
        "/7b5c8ef98b4497fe700a3647dcccc4e6.scandit",
        "/8d97762fcf3c987deeca8e790b124273.scandit",
        "/b2881842e74d4b75fa0dcbb2658f0da3.scandit",
        "/fc5e2552d2904a71a912dacaa0547efe.scandit",
        "/00918cc9b4ad74bf76111e9fa70e158e.scandit",
        "/4f10a1584fa6bfa1af2bfc95f938d192.scandit",
        "/61579472d3ab4998bfcc9e3070f39354.scandit",
        "/a5b8d6eee7ccd778f4b42d840add2539.scandit",
        "/ce62d7332b17011763bd79516d908235.scandit",
        "/ed70de938d43e92a43f5176f0fb3aef0.scandit",
        "/2dc97c75a0fafc59e91c76f766b8372d.scandit",
        "/64a3982f73cd8050fdb4b1a6e8c07537.scandit",
        "/6eb7c32c9bc81edaec9e816615538484.scandit",
        "/866c3631e1963d133c8598b60675894d.scandit",
        "/ce6c0d7ebc0081eeeb51c82beddba8a7.scandit",
        "/ffd07d94597bc9622936112d5cbacbbe.scandit", // msi-plessey
    ].map((path) => {
        return `${BlurryRecognitionPreloader.writableDataPath}${path}`;
    });
    // Roughly ordered by priority
    static availableBlurryRecognitionSymbologies = new Set([
        Barcode.Symbology.EAN13,
        Barcode.Symbology.EAN8,
        Barcode.Symbology.CODE32,
        Barcode.Symbology.CODE39,
        Barcode.Symbology.CODE128,
        Barcode.Symbology.CODE93,
        Barcode.Symbology.INTERLEAVED_2_OF_5,
        Barcode.Symbology.MSI_PLESSEY,
        Barcode.Symbology.CODABAR,
        Barcode.Symbology.UPCA,
        Barcode.Symbology.UPCE, // Shared with EAN8, EAN13, UPCA
    ]);
    eventEmitter = new EventEmitter();
    preload;
    queuedBlurryRecognitionSymbologies = Array.from(BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.values());
    readyBlurryRecognitionSymbologies = new Set();
    engineWorker;
    constructor(preload) {
        this.preload = preload;
    }
    static async create(preload) {
        if (preload) {
            // Edge <= 18 doesn't support IndexedDB in blob Web Workers so data wouldn't be persisted,
            // hence it would be useless to preload blurry recognition as data couldn't be saved.
            // Verify support for IndexedDB in blob Web Workers.
            const browserName = BrowserHelper.userAgentInfo.getBrowser().name;
            if (browserName != null && browserName.includes("Edge")) {
                const worker = new Worker(URL.createObjectURL(new Blob([`(${BlurryRecognitionPreloader.workerIndexedDBSupportTestFunction.toString()})()`], {
                    type: "text/javascript",
                })));
                return new Promise((resolve) => {
                    worker.onmessage = (message) => {
                        worker.terminate();
                        resolve(new BlurryRecognitionPreloader(message.data));
                    };
                });
            }
        }
        return new BlurryRecognitionPreloader(preload);
    }
    // istanbul ignore next
    static workerIndexedDBSupportTestFunction() {
        try {
            indexedDB.deleteDatabase("scandit_indexeddb_support_test");
            // @ts-ignore
            postMessage(true);
        }
        catch (error) {
            // @ts-ignore
            postMessage(false);
        }
    }
    async prepareBlurryTables() {
        let alreadyAvailable = true;
        if (this.preload) {
            try {
                alreadyAvailable = await this.checkBlurryTablesAlreadyAvailable();
            }
            catch (error) {
                // istanbul ignore next
                console.error(error);
            }
        }
        if (alreadyAvailable) {
            this.queuedBlurryRecognitionSymbologies = [];
            this.readyBlurryRecognitionSymbologies = new Set(BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies);
            this.eventEmitter.emit("blurryTablesUpdate", new Set(this.readyBlurryRecognitionSymbologies));
        }
        else {
            this.engineWorker = new Worker(URL.createObjectURL(dataCaptureWorkerBlob));
            this.engineWorker.onmessage = this.engineWorkerOnMessage.bind(this);
            DataCaptureLoader.load(this.engineWorker, true, true);
        }
    }
    on(eventName, listener) {
        // istanbul ignore else
        if (eventName === "blurryTablesUpdate") {
            if (this.readyBlurryRecognitionSymbologies.size ===
                BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.size) {
                listener(this.readyBlurryRecognitionSymbologies);
            }
            else {
                this.eventEmitter.on(eventName, listener);
            }
        }
    }
    updateBlurryRecognitionPriority(scanSettings) {
        const newQueuedBlurryRecognitionSymbologies = this.queuedBlurryRecognitionSymbologies.slice();
        this.getEnabledSymbologies(scanSettings).forEach((symbology) => {
            const symbologyQueuePosition = newQueuedBlurryRecognitionSymbologies.indexOf(symbology);
            if (symbologyQueuePosition !== -1) {
                newQueuedBlurryRecognitionSymbologies.unshift(newQueuedBlurryRecognitionSymbologies.splice(symbologyQueuePosition, 1)[0]);
            }
        });
        this.queuedBlurryRecognitionSymbologies = newQueuedBlurryRecognitionSymbologies;
    }
    isBlurryRecognitionAvailable(scanSettings) {
        const enabledBlurryRecognitionSymbologies = this.getEnabledSymbologies(scanSettings);
        return enabledBlurryRecognitionSymbologies.every((symbology) => {
            return this.readyBlurryRecognitionSymbologies.has(symbology);
        });
    }
    getEnabledSymbologies(scanSettings) {
        return Array.from(BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.values()).filter((symbology) => {
            return scanSettings.isSymbologyEnabled(symbology);
        });
    }
    createNextBlurryTableSymbology() {
        let symbology;
        do {
            symbology = this.queuedBlurryRecognitionSymbologies.shift();
        } while (symbology != null && this.readyBlurryRecognitionSymbologies.has(symbology));
        // istanbul ignore else
        if (symbology != null) {
            this.engineWorker.postMessage({
                type: "create-blurry-table",
                symbology,
            });
        }
    }
    checkBlurryTablesAlreadyAvailable() {
        return new Promise((resolve) => {
            const openDbRequest = indexedDB.open(BlurryRecognitionPreloader.writableDataPath);
            function handleErrorOrNew() {
                openDbRequest?.result?.close();
                // this.error
                resolve(false);
            }
            openDbRequest.onupgradeneeded = () => {
                try {
                    openDbRequest.result.createObjectStore(BlurryRecognitionPreloader.fsObjectStoreName);
                }
                catch (error) {
                    // Ignored
                }
            };
            openDbRequest.onsuccess = () => {
                try {
                    const transaction = openDbRequest.result.transaction(BlurryRecognitionPreloader.fsObjectStoreName, "readonly");
                    transaction.onerror = handleErrorOrNew;
                    const storeKeysRequest = transaction
                        .objectStore(BlurryRecognitionPreloader.fsObjectStoreName)
                        .getAllKeys();
                    storeKeysRequest.onsuccess = () => {
                        openDbRequest.result.close();
                        if ((highEndBlurryRecognition
                            ? BlurryRecognitionPreloader.highEndBlurryTableFiles
                            : BlurryRecognitionPreloader.defaultBlurryTableFiles).every((file) => {
                            return storeKeysRequest.result.indexOf(file) !== -1;
                        })) {
                            return resolve(true);
                        }
                        else {
                            return resolve(false);
                        }
                    };
                    storeKeysRequest.onerror = handleErrorOrNew;
                }
                catch (error) {
                    handleErrorOrNew.call({ error });
                }
            };
            openDbRequest.onblocked = openDbRequest.onerror = handleErrorOrNew;
        });
    }
    engineWorkerOnMessage(ev) {
        const data = ev.data;
        // istanbul ignore else
        if (data[1] != null) {
            switch (data[0]) {
                case "context-created":
                    this.createNextBlurryTableSymbology();
                    break;
                case "create-blurry-table-result":
                    this.readyBlurryRecognitionSymbologies.add(data[1]);
                    if ([Barcode.Symbology.EAN8, Barcode.Symbology.EAN13, Barcode.Symbology.UPCA, Barcode.Symbology.UPCE].includes(data[1])) {
                        this.readyBlurryRecognitionSymbologies.add(Barcode.Symbology.EAN13);
                        this.readyBlurryRecognitionSymbologies.add(Barcode.Symbology.EAN8);
                        this.readyBlurryRecognitionSymbologies.add(Barcode.Symbology.UPCA);
                        this.readyBlurryRecognitionSymbologies.add(Barcode.Symbology.UPCE);
                    }
                    else if ([Barcode.Symbology.CODE32, Barcode.Symbology.CODE39].includes(data[1])) {
                        this.readyBlurryRecognitionSymbologies.add(Barcode.Symbology.CODE32);
                        this.readyBlurryRecognitionSymbologies.add(Barcode.Symbology.CODE39);
                    }
                    this.eventEmitter.emit("blurryTablesUpdate", new Set(this.readyBlurryRecognitionSymbologies));
                    if (this.readyBlurryRecognitionSymbologies.size ===
                        BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.size) {
                        // Avoid data not being persisted if IndexedDB operations in WebWorker are slow
                        setTimeout(() => {
                            this.engineWorker.terminate();
                        }, 250);
                    }
                    else {
                        this.createNextBlurryTableSymbology();
                    }
                    break;
                // istanbul ignore next
                default:
                    break;
            }
        }
    }
}
//# sourceMappingURL=blurryRecognitionPreloader.js.map